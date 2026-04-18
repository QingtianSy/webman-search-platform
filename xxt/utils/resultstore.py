import json
import threading
from pathlib import Path

from utils.insertdata import insert_data


_INVALID_FILENAME_CHARS = '<>:"/\\|?*'
_FILENAME_TRANSLATION = str.maketrans({char: "_" for char in _INVALID_FILENAME_CHARS})
_jsonl_lock = threading.Lock()


def sanitize_filename_component(value):
    cleaned = str(value).translate(_FILENAME_TRANSLATION)
    cleaned = cleaned.replace("\t", " ").replace("\r", " ").replace("\n", " ")
    cleaned = " ".join(cleaned.split()).strip().strip(".")
    return cleaned or "unknown"


def build_result_filename(*parts):
    safe_parts = [sanitize_filename_component(part) for part in parts if str(part) != ""]
    return "-".join(safe_parts) + ".csv"


def persist_questions(self, mobile, questions_and_options_and_answers, filename_parts, course_id="", course_name=""):
    if not questions_and_options_and_answers:
        return False

    if self.output_mode == "1":
        result_path = Path("results") / build_result_filename(*filename_parts)
        existing_lines = set()
        if result_path.exists():
            existing_lines = {
                line.rstrip("\n")
                for line in result_path.read_text(encoding="utf-8").splitlines()
                if line.strip()
            }

        new_lines = []
        for item in questions_and_options_and_answers:
            question = item["question"]
            answer = item["answer"]
            options = item["options"]
            quetype = item["type"]
            if question == "":
                continue

            line = f"{quetype}\t{question}\t{options}\t{answer}"
            if line in existing_lines:
                continue
            existing_lines.add(line)
            new_lines.append(line)

        if new_lines:
            with result_path.open("a+", newline="", encoding="utf-8") as file:
                for line in new_lines:
                    file.write(f"{line}\n")
    elif self.output_mode == "3":
        _persist_jsonl(self, questions_and_options_and_answers, course_id, course_name)
    else:
        insert_data(self, questions_and_options_and_answers, course_id, course_name)

    return True


def _persist_jsonl(self, questions, course_id, course_name):
    task_no = getattr(self.gui, "task_no", "") or "default"
    result_path = Path("results") / f"{task_no}.jsonl"

    lines = []
    for item in questions:
        if not item.get("question"):
            continue
        lines.append(json.dumps({
            "quetype": item["type"],
            "question": item["question"],
            "options": item["options"],
            "answer": item["answer"],
            "course_id": course_id,
            "course_name": course_name,
        }, ensure_ascii=False))

    if lines:
        with _jsonl_lock:
            with result_path.open("a", encoding="utf-8") as f:
                for line in lines:
                    f.write(line + "\n")
