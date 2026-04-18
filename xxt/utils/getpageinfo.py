import json
import re
import time
from html import unescape
from urllib.parse import parse_qs, urlparse

import requests
from bs4 import BeautifulSoup

from utils.resultstore import persist_questions


NO_ANSWER = "__NO_ANSWER__"


def GetPageInfo(
    self,
    attachment,
    courseid,
    class_id,
    knowledge_id,
    cpi,
    session,
    course_names,
    chapter_name,
    mobile,
    type,
    point_payload=None,
    card_payload=None,
):
    try:
        context_tag = _build_chapter_context(course_names, chapter_name, knowledge_id)
        work_payload = extract_work_payload(attachment, point_payload, card_payload)
        if work_payload is None:
            self.update_text.emit(
                f"{mobile} chapter card skipped missing work payload: {context_tag}"
            )
            return False

        workid = work_payload["workid"]
        enc = work_payload["enc"]
        jobid = work_payload["jobid"]
        request_params = {
            "workId": workid,
            "courseId": courseid,
            "clazzId": class_id,
            "knowledgeId": knowledge_id,
            "jobId": jobid,
            "enc": enc,
            "cpi": cpi,
        }
        resp = _request_work_page_with_retry(
            self,
            session,
            mobile,
            course_names,
            chapter_name,
            knowledge_id,
            request_params,
        )
        if resp is None:
            return False

        soup = BeautifulSoup(resp.text, "html.parser")
        questions_and_options_and_answers = []
        parse_errors = 0

        for div in soup.find_all("div", class_="Py-mian1"):
            quetype = extract_quetype(div)
            question_node = div.find("div", class_="Py-m1-title")
            if question_node is None:
                continue

            question = question_node.get_text(strip=True)
            for img in question_node.find_all("img"):
                src = img.get("src")
                if src:
                    question += src

            cleaned_question = re.sub(r"\d+\.\[.*?\]", "", question)
            options = " | ".join(
                [
                    f"{li.get_text(strip=True)}{(li.find('img').get('src')) if li.find('img') else ''}"
                    for li in div.find_all("li", class_="clearfix")
                ]
            )

            try:
                answer = extract_answer(self, div)
                if answer == NO_ANSWER:
                    continue

                resultanswer = find_answers(self, options, answer)
                questions_and_options_and_answers.append(
                    {
                        "type": quetype,
                        "question": cleaned_question,
                        "options": options,
                        "answer": resultanswer,
                    }
                )
                self.emit_question_progress("test", type)
            except Exception:
                parse_errors += 1
                continue

        if questions_and_options_and_answers:
            persist_questions(
                self,
                mobile,
                questions_and_options_and_answers,
                [mobile, course_names, "chapter-test"],
                courseid,
                course_names,
            )
        if parse_errors:
            self.update_text.emit(
                f"{mobile} chapter parse skipped {parse_errors} malformed question(s): {context_tag}"
            )
        return bool(questions_and_options_and_answers)
    except Exception as e:
        context_tag = _build_chapter_context(course_names, chapter_name, knowledge_id)
        self.update_text.emit(f"{mobile} chapter parse failed: {context_tag}: {e}")
        return False


def _request_work_page_with_retry(
    self,
    session,
    mobile,
    course_names,
    chapter_name,
    knowledge_id,
    params,
    retries=3,
):
    last_exc = None
    endpoints = _build_work_endpoint_candidates(self.API_GETPHONEWORK)
    for endpoint in endpoints:
        for attempt in range(1, retries + 1):
            try:
                resp = session.get(
                    endpoint,
                    params=params,
                    headers={"Connection": "close"},
                    timeout=(3, 10),
                )
                resp.raise_for_status()
                return resp
            except requests.exceptions.RequestException as exc:
                last_exc = exc
                if attempt >= retries:
                    break
                time.sleep(min(1.2, 0.35 * attempt))

    context_tag = _build_chapter_context(course_names, chapter_name, knowledge_id)
    self.update_text.emit(f"{mobile} chapter work request failed: {context_tag}: {last_exc}")
    return None


def _build_work_endpoint_candidates(primary_endpoint):
    candidates = []

    def _add(url):
        if url and url not in candidates:
            candidates.append(url)

    _add(primary_endpoint)
    if isinstance(primary_endpoint, str) and "mooc1-api.chaoxing.com" in primary_endpoint:
        _add(primary_endpoint.replace("mooc1-api.chaoxing.com", "mooc1.chaoxing.com"))
    return candidates


def _build_chapter_context(course_names, chapter_name, knowledge_id):
    course_text = str(course_names or "unknown-course").strip()
    chapter_text = str(chapter_name or "unknown-chapter").strip()
    knowledge_text = str(knowledge_id or "unknown-knowledge").strip()
    return f"{course_text}-{chapter_text}-{knowledge_text}"


def extract_quetype(div):
    quetype = div.find("span", class_="quesType")
    if quetype:
        quetype = quetype.get_text(strip=True)
    else:
        quetype = "unknown"

    match = re.search(r"\[(.*?)\]", quetype)
    if match:
        return match.group(1)
    return quetype


def extract_answer(self, div):
    answers_node = div.find("div", class_="answer")
    if answers_node is None:
        return NO_ANSWER

    score_span = answers_node.find("span")
    has_score = score_span is not None and score_span.get("class") == ["fr", "pd"]

    if has_score:
        if _is_full_score(score_span.get_text(strip=True)):
            answer_tag = answers_node.find("em", class_="padRight")
            if answer_tag and answer_tag.find("i"):
                return answer_tag.find("i").get_text(strip=True)

        right_em = answers_node.find("em", class_="right-answer")
        if right_em:
            return _extract_from_right_answer(self, right_em)
        return NO_ANSWER

    emanswer = answers_node.find("em", class_="right-answer")
    if emanswer is None:
        return NO_ANSWER
    return _extract_from_right_answer(self, emanswer)


def _is_full_score(score_text):
    numbers = re.findall(r"\d+", score_text or "")
    if len(numbers) >= 2:
        return numbers[0] == numbers[-1] and int(numbers[0]) > 0
    return False


def _extract_from_right_answer(self, emanswer):
    answer_items = emanswer.find_all("div", class_="answerItem")
    if answer_items:
        p_contents = []
        for item in answer_items:
            p_tag = item.find("p")
            if p_tag:
                p_contents.append(p_tag.get_text())
        if p_contents:
            return self.separator.join(p_contents)
        return NO_ANSWER

    answer_tag = emanswer.find("i")
    if answer_tag:
        return answer_tag.get_text(strip=True)
    return NO_ANSWER


def find_answers(self, options, answer):
    if not re.match(r"^[a-zA-Z]+$", answer):
        return answer
    options_list = options.split(" | ")
    options_dict = {}
    for option in options_list:
        parts = option.split(".", 1)
        if len(parts) == 2:
            options_dict[parts[0]] = parts[1].strip()
    answer_texts = [options_dict[item] for item in answer if item in options_dict]
    if len(answer_texts) != len(answer):
        return answer
    return self.separator.join(answer_texts)


def extract_work_payload(*candidates):
    for candidate in candidates:
        payload = _extract_work_payload_from_candidate(candidate)
        if payload is not None:
            return payload
    return None


def _extract_work_payload_from_candidate(candidate, depth=0):
    if candidate is None or depth > 5:
        return None

    if isinstance(candidate, dict):
        payload = _normalize_work_payload(candidate)
        if payload is not None:
            return payload

        for key in ("src", "url", "href", "link", "data", "data_raw", "html", "raw", "content"):
            payload = _extract_payload_from_text(candidate.get(key))
            if payload is not None:
                return payload

        for value in candidate.values():
            payload = _extract_work_payload_from_candidate(value, depth + 1)
            if payload is not None:
                return payload
        return None

    if isinstance(candidate, (list, tuple, set)):
        for item in candidate:
            payload = _extract_work_payload_from_candidate(item, depth + 1)
            if payload is not None:
                return payload
        return None

    if isinstance(candidate, str):
        return _extract_payload_from_text(candidate)

    return None


def _normalize_work_payload(candidate):
    if not isinstance(candidate, dict):
        return None

    property_data = candidate.get("property")
    if isinstance(property_data, str):
        try:
            property_data = json.loads(property_data)
        except json.JSONDecodeError:
            property_data = {}
    if not isinstance(property_data, dict):
        property_data = {}

    workid = _pick_payload_value(
        candidate,
        ("workid", "workId", "work_id", "oldWorkId", "old_work_id"),
    )
    if not workid:
        workid = _pick_payload_value(
            property_data,
            ("workid", "workId", "work_id", "oldWorkId", "old_work_id"),
        )

    jobid = _pick_payload_value(
        candidate,
        ("jobid", "jobId", "job_id", "_jobid", "originJobId", "origin_job_id"),
    )
    if not jobid:
        jobid = _pick_payload_value(
            property_data,
            ("jobid", "jobId", "job_id", "_jobid", "originJobId", "origin_job_id"),
        )

    enc = _pick_payload_value(candidate, ("enc", "workEnc", "work_enc", "encWork"))
    if not enc:
        enc = _pick_payload_value(property_data, ("enc", "workEnc", "work_enc", "encWork"))
    workid, jobid = _normalize_work_and_job_id(workid, jobid)
    if not all([workid, jobid, enc]):
        return None

    return {
        "workid": workid,
        "jobid": jobid,
        "enc": str(enc).strip(),
    }


def _pick_payload_value(mapping, keys):
    if not isinstance(mapping, dict):
        return ""

    for key in keys:
        value = mapping.get(key)
        if _is_valid_payload_value(value):
            return value

    lowered = {}
    for key, value in mapping.items():
        if isinstance(key, str):
            lowered[key.lower()] = value

    for key in keys:
        value = lowered.get(str(key).lower())
        if _is_valid_payload_value(value):
            return value

    return ""


def _is_valid_payload_value(value):
    if value is None:
        return False
    text = str(value).strip()
    if not text:
        return False
    lowered = text.lower()
    return lowered not in {"none", "null", "undefined"}


def _extract_payload_from_text(text):
    if not isinstance(text, str):
        return None

    normalized = unescape(text).replace("\\u0026", "&")
    if not normalized.strip():
        return None

    workid = _extract_text_value(normalized, ("workid", "workId", "oldWorkId"))
    jobid = _extract_text_value(normalized, ("jobid", "jobId", "_jobid", "originJobId"))
    enc = _extract_text_value(normalized, ("enc",))
    workid, jobid = _normalize_work_and_job_id(workid, jobid)
    if not all([workid, jobid, enc]):
        return None

    return {
        "workid": workid,
        "jobid": jobid,
        "enc": enc,
    }


def _normalize_work_and_job_id(workid, jobid):
    workid_text = str(workid or "").strip()
    jobid_text = str(jobid or "").strip()

    if workid_text.startswith("work-"):
        workid_text = workid_text[5:]

    if not jobid_text and workid_text:
        jobid_text = f"work-{workid_text}"

    if jobid_text.startswith("work-") and not workid_text:
        workid_text = jobid_text[5:]

    return workid_text, jobid_text


def _extract_text_value(text, keys):
    parsed = urlparse(text)
    query = parse_qs(parsed.query)
    for key in keys:
        values = query.get(key) or query.get(key.lower())
        if values:
            value = values[0].strip()
            if value:
                return value

    for key in keys:
        query_match = re.search(rf"(?:[?&]|\b){re.escape(key)}=([^&\"'\s]+)", text, re.IGNORECASE)
        if query_match:
            value = query_match.group(1).strip()
            if value:
                return value

        assign_match = re.search(
            rf"[\"']?{re.escape(key)}[\"']?\s*[:=]\s*[\"']([^\"']+)[\"']",
            text,
            re.IGNORECASE,
        )
        if assign_match:
            value = assign_match.group(1).strip()
            if value:
                return value

        bare_assign_match = re.search(
            rf"[\"']?{re.escape(key)}[\"']?\s*[:=]\s*([^\s,;\"'}}]+)",
            text,
            re.IGNORECASE,
        )
        if bare_assign_match:
            value = bare_assign_match.group(1).strip()
            if value:
                return value

    return ""
