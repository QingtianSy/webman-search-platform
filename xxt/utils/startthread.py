import base64
import re
import threading
import time
from concurrent.futures import ALL_COMPLETED, ThreadPoolExecutor, wait

import requests
import urllib3
from Crypto.Cipher import AES
from Crypto.Util.Padding import pad

from utils.faceutil import fetch_face
from utils.getclasses import GetClasses
from utils.getcollect import Getcollection, GetExams


urllib3.disable_warnings()


class StartThread:
    LOGIN_MAX_ATTEMPTS = 5
    LOGIN_KEY = b"u2oh6Vu^HWe4_AES"
    RATE_BACKOFF_STEP_MS = 30
    RATE_RECOVERY_SUCCESS_THRESHOLD = 40

    def __init__(
        self,
        gui,
        mobile,
        password,
        type,
        output,
        get_separator,
        update_text,
    ):
        self.API_LOGIN_WEB = "https://passport2.chaoxing.com/fanyalogin"
        self.API_CLASS_LST = "https://mooc1-api.chaoxing.com/mycourse/backclazzdata?view=json"
        self.API_SSO_LOGIN = "https://sso.chaoxing.com/apis/login/userLogin4Uname.do"
        self.API_CHAPTER_LST = "https://mooc1-api.chaoxing.com/gas/clazz"
        self.API_CHAPTER_CARDS = "https://mooc1-api.chaoxing.com/gas/knowledge"
        self.PAGE_MOBILE_CHAPTER_CARD = "https://mooc1-api.chaoxing.com/mooc-ans/knowledge/cards"
        self.API_GETPHONEWORK = "https://mooc1-api.chaoxing.com/mooc-ans/work/phone/work"
        self.API_CAPTCHA_IMG = "https://mooc1-api.chaoxing.com/processVerifyPng.ac"
        self.API_CAPTCHA_IMG_EXAM = "https://mooc1-api.chaoxing.com/exam-ans/processVerifyPng.ac"
        self.API_CAPTCHA_SUBMIT = "https://mooc1-api.chaoxing.com/html/processVerify.ac"
        self.API_CAPTCHA_SUBMIT_EXAM = "https://mooc1-api.chaoxing.com/exam-ans/html/processVerify.ac"
        self.API_FACE_IMAGE = "https://passport2-api.chaoxing.com/api/getUserFaceid"
        self.API_FACE_SUBMIT_INFO_NEW = "https://mooc1-api.chaoxing.com/mooc-ans/facephoto/clientfacecheckstatus"
        self.API_GET_PAN_TOKEN = "https://pan-yz.chaoxing.com/api/token/uservalid"
        self.API_UPLOAD_FACE = "https://pan-yz.chaoxing.com/upload"
        self.API_EXAM_PAGE = "https://mooc1-api.chaoxing.com/exam-ans/exam/phone/task-list"
        self.API_EXAM_CONTENT = "https://mooc1-api.chaoxing.com/exam-ans/exam/phone/look-detail"
        self.API_WORK_PAGE = "https://mooc1-api.chaoxing.com/work/task-list"
        self.API_WORK_CONTENT = "https://mooc1-api.chaoxing.com/mooc-ans/work/phone/task-work"
        self.API_EXAM_ALLINFO = (
            "https://mooc1-api.chaoxing.com/exam-ans/exam/test/examcode/examlist?edition=1&nohead=0&fid="
        )

        self.gui = gui
        self.separator = get_separator
        self.db_connection = gui.dbs
        self.output_mode = output
        self.update_text = update_text
        self._thread_state = threading.local()
        self._session_lock = threading.Lock()
        self._managed_sessions = []
        self._mobile = str(mobile)
        self._rate_lock = threading.Lock()
        base_interval_ms = max(0, int(getattr(self.gui, "request_interval_ms", 0)))
        self._rate_base_interval_ms = base_interval_ms
        self._rate_bumped_interval_ms = (
            base_interval_ms + self.RATE_BACKOFF_STEP_MS if base_interval_ms > 0 else 0
        )
        self._rate_current_interval_ms = base_interval_ms
        self._rate_stable_success_count = 0
        self.completed_successfully = False

        try:
            self.completed_successfully = self.start_login(mobile, password, type)
        finally:
            self._close_managed_sessions()

    def start_login(self, mobile, password, type):
        session = self._build_session()
        session.headers = {
            "User-Agent": (
                "Mozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) "
                "AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 "
                "(schild:bd8d074ebae1daa7f16e31ae5781def3) (device:iPhone14,5) "
                "Language/zh-Hans com.ssreader.ChaoXingStudy/ChaoXingStudy_3_6.2.9_ios_phone_202406131530_236 "
                "(@Kalimdor)_8037712131006555090"
            )
        }
        self.update_text.emit(f"{mobile} 正在登录")

        success, login_data = self._phone_login(mobile, password, session)
        if not success:
            message = ""
            if isinstance(login_data, dict):
                message = login_data.get("msg") or login_data.get("message") or ""
            self.update_text.emit(f"{mobile} login failed{': ' + message if message else ''}")
            return False

        self.update_text.emit(f"{mobile} 登录成功，获取账号信息")
        infos = self.getlogin_info(session)
        if not infos:
            self.update_text.emit(f"{mobile} login info fetch failed")
            return False

        puid = infos.get("puid", "")
        try:
            fetch_face(self, puid, session)
        except Exception:
            self.update_text.emit(f"{mobile} face fetch failed, continuing")

        self.update_text.emit(f"{mobile} 正在获取课程列表")
        class_info = GetClasses(self, session)
        if class_info is False:
            self.update_text.emit(f"{mobile} class list fetch failed")
            return False
        if not class_info:
            self.update_text.emit(f"{mobile} no available classes")
            return True

        self.update_text.emit(f"{mobile} 获取到 {len(class_info)} 门课程")
        all_success = self.get_class_info(mobile, class_info, session, puid, type)
        if all_success is False:
            self.update_text.emit(f"{mobile} some courses failed during collection")
            return False
        return True

    def get_class_info(self, mobile, classes_info, session, puid, type):
        if type == "3":
            self.update_text.emit(f"{mobile} 开始处理考试列表")
            return bool(GetExams(self, mobile, session))

        target_course_ids = {str(value) for value in getattr(self.gui, "debug_course_ids", set())}
        target_class_ids = {str(value) for value in getattr(self.gui, "debug_class_ids", set())}
        target_cpi_ids = {str(value) for value in getattr(self.gui, "debug_cpi_ids", set())}
        if target_course_ids or target_class_ids or target_cpi_ids:
            filtered_classes = []
            for item in classes_info:
                course_id = str(item.get("id") or "")
                class_id = str(item.get("class_id") or "")
                cpi = str(item.get("cpi") or "")
                if target_course_ids and course_id not in target_course_ids:
                    continue
                if target_class_ids and class_id not in target_class_ids:
                    continue
                if target_cpi_ids and cpi not in target_cpi_ids:
                    continue
                filtered_classes.append(item)
            classes_info = filtered_classes
            if not classes_info:
                self.update_text.emit(f"{mobile} debug course filter did not match any class")
                return False
            self.update_text.emit(f"{mobile} debug course filter matched {len(classes_info)} class(es)")

        course_concurrency = max(1, int(getattr(self.gui, "course_concurrency", 1)))
        if course_concurrency == 1 or len(classes_info) <= 1:
            overall_success = True
            for item in classes_info:
                course_name = item.get("course_names") or item.get("id") or "unknown-course"
                self.update_text.emit(f"{mobile} 开始处理课程: {course_name}")
                result = Getcollection(self, mobile, item, session, puid, type)
                if result is False:
                    overall_success = False
                    self.update_text.emit(f"{mobile} course failed: {course_name}")
            return overall_success

        worker_count = min(course_concurrency, len(classes_info))
        self.update_text.emit(f"{mobile} 启用课程并发: {worker_count}")
        with ThreadPoolExecutor(max_workers=worker_count) as executor:
            futures = []
            for item in classes_info:
                course_name = item.get("course_names") or item.get("id") or "unknown-course"
                self.update_text.emit(f"{mobile} 开始处理课程: {course_name}")
                futures.append(
                    executor.submit(
                        self._run_course_task,
                        mobile,
                        item,
                        session,
                        puid,
                        type,
                    )
                )
            overall_success = True
            wait(futures, return_when=ALL_COMPLETED)
            for future in futures:
                course_name, result = future.result()
                if result is False:
                    overall_success = False
                    self.update_text.emit(f"{mobile} course failed: {course_name}")
        return overall_success

    def _run_course_task(self, mobile, item, base_session, puid, type):
        session = self._get_worker_session(base_session)
        course_name = item.get("course_names") or item.get("id") or "unknown-course"
        result = Getcollection(self, mobile, item, session, puid, type)
        return course_name, result

    def notify_backpressure(self, mobile, reason):
        if self._rate_base_interval_ms <= 0:
            return

        changed = False
        previous_ms = 0
        with self._rate_lock:
            self._rate_stable_success_count = 0
            if self._rate_current_interval_ms < self._rate_bumped_interval_ms:
                previous_ms = self._rate_current_interval_ms
                self._rate_current_interval_ms = self._rate_bumped_interval_ms
                changed = True

        if changed:
            identity = mobile or self._mobile
            self.update_text.emit(
                f"{identity} adaptive limiter bump: {previous_ms}ms -> {self._rate_bumped_interval_ms}ms ({reason})"
            )

    def notify_request_success(self, mobile):
        if self._rate_base_interval_ms <= 0:
            return

        restored = False
        with self._rate_lock:
            if self._rate_current_interval_ms <= self._rate_base_interval_ms:
                self._rate_stable_success_count = 0
                return

            self._rate_stable_success_count += 1
            if self._rate_stable_success_count >= self.RATE_RECOVERY_SUCCESS_THRESHOLD:
                self._rate_current_interval_ms = self._rate_base_interval_ms
                self._rate_stable_success_count = 0
                restored = True

        if restored:
            identity = mobile or self._mobile
            self.update_text.emit(f"{identity} adaptive limiter recover: {self._rate_base_interval_ms}ms")

    def _get_request_interval_seconds(self):
        with self._rate_lock:
            return self._rate_current_interval_ms / 1000

    def _build_session(self, base_session=None):
        session = requests.Session()
        if base_session is not None:
            session.headers.update(base_session.headers)
            session.cookies.update(base_session.cookies)

        original_request = session.request

        def throttled_request(method, url, **kwargs):
            try:
                request_interval = self._get_request_interval_seconds()
                if request_interval > 0:
                    last_request_at = getattr(session, "_last_request_at", 0.0)
                    now = time.monotonic()
                    wait_seconds = request_interval - (now - last_request_at)
                    if wait_seconds > 0:
                        time.sleep(wait_seconds)
                response = original_request(method, url, **kwargs)
                self.gui.record_request()
                session._last_request_at = time.monotonic()
                self.notify_request_success(getattr(session, "_mobile", self._mobile))
                return response
            except Exception:
                self.gui.record_request_error()
                self.notify_backpressure(getattr(session, "_mobile", self._mobile), "request_error")
                raise

        session._mobile = self._mobile
        session.request = throttled_request
        with self._session_lock:
            self._managed_sessions.append(session)
        return session

    def _close_managed_sessions(self):
        with self._session_lock:
            sessions = list(dict.fromkeys(self._managed_sessions))
            self._managed_sessions.clear()
        for session in sessions:
            try:
                session.close()
            except Exception:
                pass

    def _get_worker_session(self, base_session):
        session = getattr(self._thread_state, "session", None)
        if session is None:
            session = self._build_session(base_session)
            self._thread_state.session = session
        return session

    def _phone_login(self, mobile, password, session):
        resp = session.post(
            self.API_LOGIN_WEB,
            data={
                "fid": -1,
                "uname": self._encrypt_value(mobile),
                "password": self._encrypt_value(password),
                "t": "true",
                "forbidotherlogin": 0,
                "validate": "",
            },
            verify=False,
            timeout=3,
        )
        json_content = resp.json()
        if json_content.get("status") is True:
            return True, json_content
        return False, json_content

    def _encrypt_value(self, value):
        cryptor = AES.new(self.LOGIN_KEY, AES.MODE_CBC, self.LOGIN_KEY)
        encrypted = cryptor.encrypt(pad(str(value).encode(), 16))
        return base64.b64encode(encrypted).decode()

    def emit_question_progress(self, page_type, collect_type):
        total = self.gui.increment_page(page_type)
        if collect_type == "1" and page_type == "test":
            if self._should_log_progress(total):
                self.update_text.emit(f"Chapter questions: {total}")
            return
        if collect_type == "2" and page_type == "exam":
            if self._should_log_progress(total):
                self.update_text.emit(f"Exam questions: {total}")
            return
        if collect_type == "3" and page_type == "exam":
            if self._should_log_progress(total):
                self.update_text.emit(f"Exam list questions: {total}")
            return
        if collect_type == "4" and page_type == "home":
            if self._should_log_progress(total):
                self.update_text.emit(f"Homework questions: {total}")
            return

        counts = self.gui.increment_aggregation(page_type)
        if self._should_log_progress(counts.get(page_type, 0)):
            self.update_text.emit(
                f"Tests: {counts['test']}    "
                f"Exams: {counts['exam']}    "
                f"Homework: {counts['home']}"
            )

    def _should_log_progress(self, total):
        interval = max(1, int(getattr(self.gui, "progress_log_interval", 10)))
        return total <= 3 or total % interval == 0

    def getlogin_info(self, session):
        try:
            resp = session.get(self.API_SSO_LOGIN, timeout=5)
            json_content = resp.json()
            if json_content.get("result") == 0:
                return False
            return {
                "puid": json_content["msg"]["puid"],
                "name": json_content["msg"]["name"],
                "sex": json_content["msg"]["sex"],
                "phone": json_content["msg"]["phone"],
                "schoolname": json_content["msg"]["schoolname"],
                "uname": json_content["msg"].get("uname"),
            }
        except Exception:
            return False
