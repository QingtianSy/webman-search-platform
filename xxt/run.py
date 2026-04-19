from __future__ import annotations

import argparse
import configparser
import re
import sys
import threading
from concurrent.futures import FIRST_COMPLETED, ThreadPoolExecutor, wait
from datetime import datetime
from pathlib import Path
from typing import Iterable
from urllib.parse import parse_qs, urlparse


ROUTE_TO_TYPE = {
    "courses": "5",
    "course": "5",
    "chapter": "1",
    "exam": "2",
    "exam-list": "3",
    "homework": "4",
}

OUTPUT_TO_TYPE = {
    "local": "1",
    "sql": "2",
    "json": "3",
}


class SignalAdapter:
    def __init__(self, callback):
        self.callback = callback

    def emit(self, value):
        self.callback(value)


class SharedState:
    def __init__(
        self,
        db_config,
        total_accounts,
        course_concurrency,
        request_interval_ms,
        error_log_file,
        debug_knowledge_ids,
        debug_course_ids,
        debug_class_ids,
        debug_cpi_ids,
        debug_chapter_ids,
        task_no="",
        proxy="",
    ):
        self.db_config = db_config
        self.task_no = task_no
        self.proxy = proxy
        self.course_concurrency = course_concurrency
        self.request_interval_ms = request_interval_ms
        self.error_log_file = Path(error_log_file).expanduser() if error_log_file else None
        self.debug_knowledge_ids = set(debug_knowledge_ids)
        self.debug_course_ids = set(debug_course_ids)
        self.debug_class_ids = set(debug_class_ids)
        self.debug_cpi_ids = set(debug_cpi_ids)
        self.debug_chapter_ids = set(debug_chapter_ids)
        self.progress_log_interval = 10
        self.aggregation = {"test": 0, "exam": 0, "home": 0}
        self.tiku_num = 0
        self.tiku_num_exam = 0
        self.tiku_num_home = 0
        self.metrics = {
            "requests": 0,
            "request_errors": 0,
            "invalid_verify_hits": 0,
            "anti_spider_verifies": 0,
            "face_verification_required": 0,
            "face_verification_failed": 0,
        }
        self.total_threads = total_accounts
        self.completed_threads = 0
        self._lock = threading.Lock()

    def ensure_paths(self):
        Path("faces").mkdir(exist_ok=True)
        Path("results").mkdir(exist_ok=True)
        if self.error_log_file is not None:
            self.error_log_file.parent.mkdir(parents=True, exist_ok=True)

    def on_text(self, text):
        with self._lock:
            print(text)
            if self._should_persist_error(text):
                self._append_error_log(text)

    def start_error_log_session(self):
        if self.error_log_file is None:
            return
        with self._lock:
            self._append_error_log("===== collection run started =====")

    def _should_persist_error(self, text):
        lower_text = str(text).lower()
        keywords = (
            "failed",
            "failure",
            "error",
            "exception",
            "traceback",
            "timeout",
            "timed out",
            "request failed",
            "parse failed",
            "retry exhausted",
            "remote disconnected",
            "remote end closed connection",
            "chapter card skipped",
            "missing work payload",
            "失败",
            "错误",
            "超时",
            "异常",
        )
        return any(keyword in lower_text for keyword in keywords)

    def _append_error_log(self, text):
        if self.error_log_file is None:
            return
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        with self.error_log_file.open("a+", encoding="utf-8") as handle:
            handle.write(f"[{timestamp}] {text}\n")

    def increment_page(self, page_type):
        with self._lock:
            if page_type == "test":
                self.tiku_num += 1
                return self.tiku_num
            if page_type == "exam":
                self.tiku_num_exam += 1
                return self.tiku_num_exam
            if page_type == "home":
                self.tiku_num_home += 1
                return self.tiku_num_home
            return 0

    def increment_aggregation(self, page_type):
        with self._lock:
            if page_type in self.aggregation:
                self.aggregation[page_type] += 1
            return dict(self.aggregation)

    def record_request(self):
        with self._lock:
            self.metrics["requests"] += 1

    def record_request_error(self):
        with self._lock:
            self.metrics["request_errors"] += 1

    def increment_metric(self, metric_name, amount=1):
        with self._lock:
            if metric_name in self.metrics:
                self.metrics[metric_name] += amount

    def snapshot_metrics(self):
        with self._lock:
            return dict(self.metrics)

    def thread_finished(self, mobile, password, is_success):
        with self._lock:
            self.completed_threads += 1
            completed_threads = self.completed_threads
            target_file = "采集成功.txt" if is_success else "采集错误.txt"
            with open(target_file, "a+", encoding="utf-8") as handle:
                handle.write(f"{mobile}----{password}\n")

            status = "成功" if is_success else "失败"
            print(f"[{completed_threads}/{self.total_threads}] {mobile} {status}", flush=True)


class WorkerContext:
    def __init__(self, shared_state, db_connection):
        self._shared_state = shared_state
        self.dbs = db_connection
        self.db_config = shared_state.db_config
        self.task_no = shared_state.task_no
        self.proxy = shared_state.proxy
        self.course_concurrency = shared_state.course_concurrency
        self.request_interval_ms = shared_state.request_interval_ms
        self.debug_knowledge_ids = set(shared_state.debug_knowledge_ids)
        self.debug_course_ids = set(shared_state.debug_course_ids)
        self.debug_class_ids = set(shared_state.debug_class_ids)
        self.debug_cpi_ids = set(shared_state.debug_cpi_ids)
        self.debug_chapter_ids = set(shared_state.debug_chapter_ids)
        self.progress_log_interval = shared_state.progress_log_interval

    def increment_page(self, page_type):
        return self._shared_state.increment_page(page_type)

    def increment_aggregation(self, page_type):
        return self._shared_state.increment_aggregation(page_type)

    def record_request(self):
        self._shared_state.record_request()

    def record_request_error(self):
        self._shared_state.record_request_error()

    def increment_metric(self, metric_name, amount=1):
        self._shared_state.increment_metric(metric_name, amount)


def parse_account_line(line):
    content = line.strip()
    if not content or content.startswith("#"):
        return None

    normalized = content.replace(" ", "----")
    parts = normalized.split("----")
    mobile_pattern = r"^1[3-9]\d{9}$"

    if len(parts) == 2:
        if re.match(mobile_pattern, parts[0]):
            return parts[0], parts[1]
        return None

    if len(parts) == 3:
        if re.match(mobile_pattern, parts[1]):
            return parts[1], parts[2]
        return None

    return None


def load_accounts(account_file, inline_accounts):
    seen = set()
    accounts = []

    for raw_account in inline_accounts:
        account = parse_account_line(raw_account)
        if account and account not in seen:
            seen.add(account)
            accounts.append(account)

    if account_file is not None:
        for line in account_file.read_text(encoding="utf-8").splitlines():
            account = parse_account_line(line)
            if account and account not in seen:
                seen.add(account)
                accounts.append(account)

    return accounts


def parse_debug_knowledge_ids(raw_value):
    if not raw_value:
        return set()
    return {item for item in re.split(r"[,\s]+", raw_value.strip()) if item}


def parse_debug_study_url(raw_url):
    if not raw_url:
        return {"course_ids": set(), "class_ids": set(), "cpi_ids": set(), "chapter_ids": set()}

    parsed = urlparse(raw_url.strip())
    query = parse_qs(parsed.query)

    def _first(name):
        values = query.get(name) or []
        return values[0].strip() if values and values[0].strip() else ""

    course_id = _first("courseId")
    class_id = _first("clazzid") or _first("classId")
    cpi = _first("cpi")
    chapter_id = _first("chapterId")

    return {
        "course_ids": {course_id} if course_id else set(),
        "class_ids": {class_id} if class_id else set(),
        "cpi_ids": {cpi} if cpi else set(),
        "chapter_ids": {chapter_id} if chapter_id else set(),
    }


def load_db_config(config_path):
    parser = configparser.ConfigParser()
    if not config_path.exists():
        raise FileNotFoundError(f"数据库配置文件不存在: {config_path}")

    parser.read(config_path, encoding="utf-8")
    if "MySQLConfig" not in parser:
        raise ValueError("config.ini 缺少 MySQLConfig 配置段")

    config = dict(parser.items("MySQLConfig"))
    required_keys = ("ip", "port", "database", "user", "password", "table")
    missing_keys = [key for key in required_keys if not config.get(key)]
    if missing_keys:
        raise ValueError(f"MySQLConfig 缺少必要字段: {', '.join(missing_keys)}")

    return {
        "db_address": config["ip"],
        "db_port": config["port"],
        "db_name": config["database"],
        "db_user": config["user"],
        "db_password": config["password"],
        "db_table": config["table"],
    }


def create_db_connection(db_config):
    import pymysql

    return pymysql.connect(
        host=db_config["db_address"],
        port=int(db_config["db_port"]),
        user=db_config["db_user"],
        password=db_config["db_password"],
        db=db_config["db_name"],
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )


def run_single_account(shared_state, account, collect_type, output_mode, separator):
    from utils.startthread import StartThread

    mobile, password = account
    db_connection = None
    account_success = False

    try:
        if output_mode == "2":
            db_connection = create_db_connection(shared_state.db_config)

        worker_context = WorkerContext(shared_state, db_connection)
        runner = StartThread(
            worker_context,
            mobile,
            password,
            collect_type,
            output_mode,
            separator,
            SignalAdapter(shared_state.on_text),
        )
        account_success = bool(getattr(runner, "completed_successfully", False))
    except Exception as exc:
        shared_state.on_text(f"{mobile} 处理失败: {exc}")
    finally:
        if db_connection is not None:
            db_connection.close()
        shared_state.thread_finished(mobile, password, account_success)


def build_parser():
    parser = argparse.ArgumentParser(description="学习通采集命令行入口")
    parser.add_argument(
        "--accounts-file",
        type=Path,
        help="账号文件路径，支持 手机号----密码 格式",
    )
    parser.add_argument(
        "--account",
        action="append",
        default=[],
        help="直接传入单条账号，格式同账号文件，可重复传入",
    )
    parser.add_argument(
        "--mode",
        choices=sorted(ROUTE_TO_TYPE),
        default="courses",
        help="采集模式: courses(整号) course(单课程) chapter(章节测试) exam(考试) homework(作业)",
    )
    parser.add_argument(
        "--output",
        choices=sorted(OUTPUT_TO_TYPE),
        default="local",
        help="导出模式",
    )
    parser.add_argument(
        "--separator",
        default="###",
        help="多答案拼接分隔符",
    )
    parser.add_argument(
        "--concurrency",
        type=int,
        default=10,
        help="账号并发数，范围 1-100",
    )
    parser.add_argument(
        "--course-concurrency",
        type=int,
        default=1,
        help="单账号内部课程并发数，范围 1-10",
    )
    parser.add_argument(
        "--request-interval-ms",
        type=int,
        default=120,
        help="单个 worker 每次请求之间的间隔（毫秒），范围 0-5000",
    )
    parser.add_argument(
        "--error-log-file",
        default="error_events.txt",
        help="Write error-like logs to this txt file",
    )
    parser.add_argument(
        "--debug-knowledge-id",
        default="",
        help="Only run specific knowledge_id values, split by comma or space",
    )
    parser.add_argument(
        "--course-ids",
        default="",
        help="Only collect specific course IDs, comma-separated",
    )
    parser.add_argument(
        "--task-no",
        default="",
        help="Task number for JSON output mode, used as output filename",
    )
    parser.add_argument(
        "--debug-study-url",
        default="",
        help="Lock collection to the course/chapter from a studentstudy URL",
    )
    parser.add_argument(
        "--db-config",
        type=Path,
        default=Path("config.ini"),
        help="MySQL 配置文件路径，仅 output=sql 时使用",
    )
    parser.add_argument(
        "--proxy",
        default="",
        help="代理地址，格式: protocol://[user:pass@]host:port",
    )
    return parser


def validate_args(args):
    if args.accounts_file is None and not args.account:
        raise ValueError("请至少提供 --accounts-file 或 --account")

    if args.accounts_file is not None and not args.accounts_file.exists():
        raise FileNotFoundError(f"账号文件不存在: {args.accounts_file}")

    if args.concurrency < 1 or args.concurrency > 100:
        raise ValueError("账号并发数必须在 1 到 100 之间")

    if args.course_concurrency < 1 or args.course_concurrency > 10:
        raise ValueError("课程并发数必须在 1 到 10 之间")

    if args.request_interval_ms < 0 or args.request_interval_ms > 5000:
        raise ValueError("请求间隔必须在 0 到 5000 毫秒之间")


def run_collection(args):
    validate_args(args)
    accounts = load_accounts(args.accounts_file, args.account)
    if not accounts:
        raise ValueError("没有解析到可用账号")

    debug_knowledge_ids = parse_debug_knowledge_ids(args.debug_knowledge_id)
    debug_scope = parse_debug_study_url(args.debug_study_url)
    explicit_course_ids = parse_debug_knowledge_ids(args.course_ids)

    db_config = {}
    if args.output == "sql":
        db_config = load_db_config(args.db_config)

    shared_state = SharedState(
        db_config=db_config,
        total_accounts=len(accounts),
        course_concurrency=args.course_concurrency,
        request_interval_ms=args.request_interval_ms,
        error_log_file=args.error_log_file,
        debug_knowledge_ids=debug_knowledge_ids,
        debug_course_ids=explicit_course_ids | debug_scope["course_ids"],
        debug_class_ids=debug_scope["class_ids"],
        debug_cpi_ids=debug_scope["cpi_ids"],
        debug_chapter_ids=debug_scope["chapter_ids"],
        task_no=args.task_no,
        proxy=args.proxy,
    )
    shared_state.ensure_paths()
    shared_state.start_error_log_session()

    debug_suffix = ""
    if debug_knowledge_ids:
        debug_suffix = f" knowledge_filter={','.join(sorted(debug_knowledge_ids))}"
    if debug_scope["course_ids"] or explicit_course_ids:
        all_course_ids = sorted(explicit_course_ids | debug_scope["course_ids"])
        debug_suffix += f" course_filter={','.join(all_course_ids)}"
    if debug_scope["class_ids"]:
        debug_suffix += f" class_filter={','.join(sorted(debug_scope['class_ids']))}"
    if debug_scope["cpi_ids"]:
        debug_suffix += f" cpi_filter={','.join(sorted(debug_scope['cpi_ids']))}"
    if debug_scope["chapter_ids"]:
        debug_suffix += f" chapter_filter={','.join(sorted(debug_scope['chapter_ids']))}"
    if shared_state.error_log_file is not None:
        debug_suffix += f" error_log={shared_state.error_log_file}"
    if args.proxy:
        debug_suffix += f" proxy={args.proxy}"

    print(
        f"开始采集: 账号数={len(accounts)} 模式={args.mode} 导出={args.output} "
        f"账号并发={args.concurrency} 课程并发={args.course_concurrency} "
        f"请求间隔={args.request_interval_ms}ms{debug_suffix}",
        flush=True,
    )

    collect_type = ROUTE_TO_TYPE[args.mode]
    output_mode = OUTPUT_TO_TYPE[args.output]

    if args.concurrency == 1 or len(accounts) == 1:
        for account in accounts:
            run_single_account(
                shared_state,
                account,
                collect_type,
                output_mode,
                args.separator,
            )
    else:
        with ThreadPoolExecutor(max_workers=args.concurrency) as executor:
            future_map = {
                executor.submit(
                    run_single_account,
                    shared_state,
                    account,
                    collect_type,
                    output_mode,
                    args.separator,
                ): account[0]
                for account in accounts
            }
            pending = set(future_map.keys())
            while pending:
                done, pending = wait(pending, timeout=5, return_when=FIRST_COMPLETED)
                if not done:
                    pending_accounts = [future_map[future] for future in pending]
                    shared_state.on_text(
                        f"waiting account tasks: pending={len(pending)} mobiles={','.join(pending_accounts)}"
                    )
                    continue
                for future in done:
                    future.result()

    metrics = shared_state.snapshot_metrics()
    print(
        "运行统计: "
        f"requests={metrics['requests']} "
        f"request_errors={metrics['request_errors']} "
        f"invalid_verify={metrics['invalid_verify_hits']} "
        f"anti_spider={metrics['anti_spider_verifies']} "
        f"face_required={metrics['face_verification_required']} "
        f"face_failed={metrics['face_verification_failed']}",
        flush=True,
    )
    print("采集任务已结束", flush=True)


def main(argv: Iterable[str] | None = None):
    parser = build_parser()
    args = parser.parse_args(list(argv) if argv is not None else None)

    try:
        run_collection(args)
    except Exception as exc:
        print(f"运行失败: {exc}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
