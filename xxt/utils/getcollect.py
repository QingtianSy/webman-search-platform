import json
import re
import time
from html import unescape
from urllib.parse import parse_qs, urljoin, urlparse

import requests
from bs4 import BeautifulSoup

from utils.faceutil import get_ts
from utils.getpageinfo import GetPageInfo, extract_work_payload, find_answers
from utils.resultstore import persist_questions
from utils.uploadface import UploadFace
from utils.verrun import VerRun, VerRunExam


def _request_with_retry(self, session, method, url, mobile, *, timeout=(3, 8), retries=3, **kwargs):
    last_exc = None
    for attempt in range(1, retries + 1):
        try:
            resp = session.request(method, url, timeout=timeout, **kwargs)
            resp.raise_for_status()
            return resp
        except requests.exceptions.RequestException as exc:
            last_exc = exc
            if attempt >= retries:
                break
            time.sleep(min(1.0, 0.35 * attempt))
    raise last_exc


def Getcollection(self, mobile, item, session, puid, type):
    try:
        courseid = item.get("id")
        class_id = item.get("class_id")
        cpi = item.get("cpi")
        key = item.get("key")
        course_names = item.get("course_names")

        if type == "1":
            self.update_text.emit(f"{mobile} 进入章节采集: {course_names}")
            return tests(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names) is True
        if type == "2":
            self.update_text.emit(f"{mobile} 进入考试采集: {course_names}")
            return exam(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names) is True
        if type == "4":
            self.update_text.emit(f"{mobile} 进入作业采集: {course_names}")
            return homework(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names) is True
        if type == "5":
            self.update_text.emit(f"{mobile} 进入综合采集: {course_names}")
            test_result = tests(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names)
            exam_result = exam(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names)
            homework_result = homework(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names)
            return all(result is True for result in (test_result, exam_result, homework_result))
        return False
    except Exception as e:
        self.update_text.emit(f"{mobile} collect failed: {e}")
        return False


def GetExams(self, mobile, session):
    try:
        all_hrefs = []
        start_value = 0

        for _ in range(50):
            resp = session.post(
                self.API_EXAM_ALLINFO,
                data={
                    "start": start_value,
                    "nohead": 0,
                    "fid": "",
                    "status": -1,
                    "clientexam": -1,
                    "sw": "",
                },
                timeout=5,
            )
            resp.raise_for_status()
            soup = BeautifulSoup(resp.text, "html.parser")
            tr_tags = soup.find_all("tr", class_="dataTr")

            for tr_tag in tr_tags:
                td_tags = tr_tag.find_all("td")
                link_tag = tr_tag.find("a", class_="col_blue")
                onclick_href = link_tag.get("onclick") if link_tag else ""
                href_matches = re.findall(r"'(.*?)'", onclick_href)
                if len(td_tags) <= 1 or not href_matches:
                    continue
                all_hrefs.append((td_tags[1].get_text(strip=True), href_matches[0]))

            if len(tr_tags) != 12:
                break
            start_value += 12

        for course_names, hrefurl in all_hrefs:
            final_url = _resolve_exam_url(self, session, mobile, hrefurl)
            if not final_url:
                continue

            course_id = get_query_parameter(final_url, "courseId")
            class_id = get_query_parameter(final_url, "classId")
            exam_id = get_query_parameter(final_url, "examId")
            exam_answer_id = get_query_parameter(final_url, "examAnswerId")
            cpi = get_query_parameter(final_url, "cpi")
            if not all([course_id, class_id, exam_id, exam_answer_id]):
                continue

            questions = _fetch_exam_questions(
                self,
                session,
                course_id,
                class_id,
                exam_id,
                cpi,
                exam_answer_id,
                lambda: _emit_exam_list_progress(self),
            )
            if questions:
                persist_questions(
                    self,
                    mobile,
                    questions,
                    [course_id, mobile, course_names, f"exam-{exam_answer_id}"],
                    course_id,
                    course_names,
                )
        return True
    except Exception as e:
        self.update_text.emit(f"{mobile} exam-list failed: {e}")
        return False


def tests(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names):
    try:
        self.update_text.emit(f"{mobile} 正在读取章节列表: {course_names}")
        chapter_params = {
            "id": key,
            "personid": cpi,
            "fields": (
                "id,bbsid,hideclazz,classscore,isstart,forbidintoclazz,allowdownload,chatid,name,state,isfiled,"
                "information,visiblescore,begindate,coursesetting.fields(id,courseid,hiddencoursecover,coursefacecheck),"
                "course.fields(id,name,infocontent,objectid,app,bulletformat,mappingcourseid,imageurl,"
                "knowledge.fields(id,name,indexOrder,parentnodeid,status,layer,label,jobcount,begintime,endtime,"
                "attachment.fields(id,type,objectid,extension).type(video)))"
            ),
            "view": "json",
        }
        chapter_json = _get_json_with_verify(self, session, mobile, self.API_CHAPTER_LST, chapter_params, VerRun)
        if _has_collection_error(chapter_json):
            return False
        chapter_lst = _safe_get_chapter_list(chapter_json)
        if not chapter_lst:
            self.update_text.emit(f"{mobile} 课程暂无可采集章节: {course_names}")
            return True

        target_knowledge_ids = {str(item) for item in getattr(self.gui, "debug_knowledge_ids", set())}
        target_chapter_ids = {str(item) for item in getattr(self.gui, "debug_chapter_ids", set())}
        matched_target = False
        matched_chapter = False
        course_success = True

        if target_chapter_ids:
            filtered_chapters = []
            for chapter in chapter_lst:
                chapter_id = str(chapter.get("id") or "")
                if chapter_id in target_chapter_ids:
                    filtered_chapters.append(chapter)
            if not filtered_chapters:
                self.update_text.emit(f"{mobile} debug chapter not found in course: {course_names}")
                return True
            chapter_lst = filtered_chapters
            self.update_text.emit(f"{mobile} debug chapter filter matched {len(chapter_lst)} chapter(s)")

        self.update_text.emit(f"{mobile} 课程 {course_names} 共 {len(chapter_lst)} 个章节")

        for chapter_index, chapter in enumerate(chapter_lst, start=1):
            chapter_name = chapter.get("name") or chapter.get("id") or f"chapter-{chapter_index}"
            chapter_id = str(chapter.get("id") or "")
            if target_chapter_ids:
                matched_chapter = True
                self.update_text.emit(f"{mobile} debug chapter hit: {chapter_id}")
            self.update_text.emit(
                f"{mobile} 正在处理章节 {chapter_index}/{len(chapter_lst)}: {chapter_name}"
            )
            knowledge_params = {
                "id": chapter.get("id"),
                "courseid": courseid,
                "fields": (
                    "id,parentnodeid,indexorder,label,layer,name,begintime,createtime,lastmodifytime,status,"
                    "jobUnfinishedCount,clickcount,openlock,card.fields(id,knowledgeid,title,knowledgeTitile,"
                    "description,cardorder).contentcard(all)"
                ),
                "view": "json",
                "token": "4faa8662c59590c6f43ae9fe5b002b42",
                "_time": get_ts(),
            }
            cards_json = _get_json_with_verify(self, session, mobile, self.API_CHAPTER_CARDS, knowledge_params, VerRun)
            if _has_collection_error(cards_json):
                course_success = False
                continue
            cards = _safe_get_cards(cards_json)
            if not cards:
                self.update_text.emit(f"{mobile} 章节无卡片内容: {chapter_name}")
                continue

            card_items = list(enumerate(cards))
            if target_knowledge_ids:
                matching_items = []
                available_knowledge_ids = []
                for card_index, card in card_items:
                    knowledge_id = str(card.get("knowledgeid") or "")
                    if knowledge_id:
                        available_knowledge_ids.append(knowledge_id)
                    if knowledge_id in target_knowledge_ids:
                        matching_items.append((card_index, card))

                if matching_items:
                    card_items = matching_items
                    matched_target = True
                    for _, card in matching_items:
                        self.update_text.emit(f"{mobile} debug knowledge hit: {card.get('knowledgeid')}")
                else:
                    shown_ids = ",".join(available_knowledge_ids[:15]) if available_knowledge_ids else "none"
                    self.update_text.emit(
                        f"{mobile} debug knowledge not found in chapter {chapter_id}; available={shown_ids}"
                    )

            for card_index, card in card_items:
                description = card.get("description")
                knowledge_id = card.get("knowledgeid")
                if not description or not knowledge_id:
                    continue

                attachment = _fetch_work_attachment(
                    self,
                    mobile,
                    session,
                    puid,
                    courseid,
                    class_id,
                    course_names,
                    chapter_name,
                    knowledge_id,
                    cpi,
                    card_index,
                    card,
                )
                if attachment is None:
                    attachment = {}
                has_attachment_payload = extract_work_payload(attachment) is not None
                inline_html = BeautifulSoup(description, "lxml")
                work_points = [point for point in inline_html.find_all("iframe") if point.get("module") == "work"]
                if not work_points:
                    if not has_attachment_payload:
                        continue
                    page_success = GetPageInfo(
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
                        card_payload=card,
                    )
                    if page_success is False:
                        course_success = False
                    continue

                for point in work_points:
                    point_payload = _build_point_payload(point)
                    if point_payload is None and not has_attachment_payload:
                        continue

                    page_success = GetPageInfo(
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
                        point_payload=point_payload,
                        card_payload=card,
                    )
                    if page_success is False:
                        course_success = False
        if target_chapter_ids and not matched_chapter:
            self.update_text.emit(f"{mobile} debug chapter not found in course: {course_names}")
        if target_knowledge_ids and not matched_target:
            self.update_text.emit(f"{mobile} debug knowledge filter was not matched in course: {course_names}")
        self.update_text.emit(f"{mobile} 课程章节采集完成: {course_names}")
        return course_success
    except Exception as e:
        self.update_text.emit(f"{mobile} tests failed: {e}")
        return False


def exam(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names):
    try:
        self.update_text.emit(f"{mobile} 正在读取考试列表: {course_names}")
        resp = session.get(
            self.API_EXAM_PAGE,
            params={"courseId": courseid, "classId": class_id, "cpi": cpi},
            timeout=3,
        )
        resp.raise_for_status()

        gotasks = re.findall(r"""<li onclick="goTask\(this\);" data="(.*?)">""", resp.text)
        for gotask in gotasks:
            final_url = _resolve_exam_url(self, session, mobile, gotask)
            if not final_url:
                continue

            final_class_id = get_query_parameter(final_url, "classId")
            course_id = get_query_parameter(final_url, "courseId")
            exam_id = get_query_parameter(final_url, "examId")
            final_cpi = get_query_parameter(final_url, "cpi")
            exam_answer_id = get_query_parameter(final_url, "examAnswerId")
            if not all([final_class_id, course_id, exam_id, exam_answer_id]):
                continue

            questions = _fetch_exam_questions(
                self,
                session,
                course_id,
                final_class_id,
                exam_id,
                final_cpi,
                exam_answer_id,
                lambda: _emit_exam_progress(self, type),
            )
            if questions:
                persist_questions(
                    self,
                    mobile,
                    questions,
                    [course_id, mobile, course_names, f"exam-{exam_answer_id}"],
                    course_id,
                    course_names,
                )
        return True
    except Exception as e:
        self.update_text.emit(f"{mobile} exam failed: {e}")
        return False


def homework(self, mobile, item, session, puid, type, courseid, class_id, cpi, key, course_names):
    try:
        self.update_text.emit(f"{mobile} 正在读取作业列表: {course_names}")
        resp = session.get(
            self.API_WORK_PAGE,
            params={"courseId": courseid, "classId": class_id, "cpi": cpi},
            timeout=3,
        )
        resp.raise_for_status()

        gotasks = re.findall(r"""<li onclick="goTask\(this\);" data="(.*?)" data1""", resp.text)
        for gotask in gotasks:
            taskref_id = get_query_parameter(gotask, "taskrefId")
            course_id = get_query_parameter(gotask, "courseId")
            task_class_id = get_query_parameter(gotask, "clazzId")
            user_id = get_query_parameter(gotask, "userId")
            enc_task = get_query_parameter(gotask, "enc_task")
            if not all([taskref_id, course_id, task_class_id, user_id]):
                continue

            resp = session.get(
                self.API_WORK_CONTENT,
                params={
                    "taskrefId": taskref_id,
                    "courseId": course_id,
                    "classId": task_class_id,
                    "userId": user_id,
                    "role": "",
                    "enc_task": "0",
                    "source": enc_task,
                    "cpi": cpi,
                },
                timeout=3,
            )
            resp.raise_for_status()

            review_url, question_link_id = _build_homework_review_url(session, resp.text, course_id, task_class_id)
            if not review_url or not question_link_id:
                continue

            review_resp = session.get(review_url, timeout=3)
            review_resp.raise_for_status()
            soup = BeautifulSoup(review_resp.text, "html.parser")
            questions = []

            for question_block in soup.find_all("div", class_="slideHeight ans-cc fontLabel singleQuesId"):
                question_data = _extract_homework_entry(self, question_block)
                if question_data is None:
                    continue
                questions.append(question_data)
                _emit_homework_progress(self, type)

            if questions:
                persist_questions(
                    self,
                    mobile,
                    questions,
                    [mobile, course_names, f"homework-{question_link_id}"],
                    course_id,
                    course_names,
                )
        return True
    except Exception as e:
        self.update_text.emit(f"{mobile} homework failed: {e}")
        return False


def _get_json_with_verify(self, session, mobile, url, params, verify_func):
    try:
        resp = _request_with_retry(self, session, "get", url, mobile, params=params)
        json_content = resp.json()
    except requests.exceptions.RequestException as exc:
        path = urlparse(url).path
        self.update_text.emit(f"{mobile} request retry exhausted: {path}: {exc}")
        return {"error": "request_failed"}
    except ValueError:
        path = urlparse(url).path
        self.update_text.emit(f"{mobile} invalid json response: {path}")
        return {"error": "invalid_json"}

    if json_content.get("error") != "invalid_verify":
        return json_content

    self.gui.increment_metric("invalid_verify_hits")
    if hasattr(self, "notify_backpressure"):
        self.notify_backpressure(mobile, "invalid_verify")
    for _ in range(10):
        if verify_func(self, mobile, session):
            try:
                resp = _request_with_retry(self, session, "get", url, mobile, params=params)
                json_content = resp.json()
            except requests.exceptions.RequestException as exc:
                path = urlparse(url).path
                self.update_text.emit(f"{mobile} verify retry exhausted: {path}: {exc}")
                return {"error": "request_failed"}
            except ValueError:
                path = urlparse(url).path
                self.update_text.emit(f"{mobile} invalid json response after verify: {path}")
                return {"error": "invalid_json"}
            if json_content.get("error") != "invalid_verify":
                return json_content
            self.gui.increment_metric("invalid_verify_hits")
            if hasattr(self, "notify_backpressure"):
                self.notify_backpressure(mobile, "invalid_verify")
    return json_content


def _has_collection_error(json_content):
    if not isinstance(json_content, dict):
        return False
    return json_content.get("error") in {"request_failed", "invalid_json", "invalid_verify"}


def _safe_get_chapter_list(json_content):
    try:
        return json_content["data"][0]["course"]["data"][0]["knowledge"]["data"]
    except (KeyError, IndexError, TypeError):
        return []


def _safe_get_cards(json_content):
    try:
        return json_content["data"][0]["card"]["data"]
    except (KeyError, IndexError, TypeError):
        return []


def _fetch_work_attachment(
    self,
    mobile,
    session,
    puid,
    courseid,
    class_id,
    course_names,
    chapter_name,
    knowledge_id,
    cpi,
    card_index,
    card,
):
    context_tag = _build_chapter_context(course_names, chapter_name, knowledge_id)
    num_candidates = _build_card_num_candidates(card_index, card)
    last_resp = None
    for num in num_candidates:
        params = {
            "clazzid": class_id,
            "courseid": courseid,
            "knowledgeid": knowledge_id,
            "num": num,
            "isPhone": 1,
            "control": "true",
            "cpi": cpi,
        }
        try:
            resp = _request_with_retry(
                self,
                session,
                "get",
                self.PAGE_MOBILE_CHAPTER_CARD,
                mobile,
                params=params,
            )
        except requests.exceptions.RequestException:
            continue

        last_resp = resp
        attachment = _extract_attachment_setting(resp.text)
        if attachment is None:
            attachment = _extract_attachment_from_iframes(self, mobile, session, resp.text, resp.url)
        if attachment is None:
            attachment = extract_work_payload(resp.text, resp.url)
        if attachment is not None:
            return attachment

    if last_resp is None:
        self.update_text.emit(f"{mobile} chapter card request failed: {context_tag}")
        return None

    page_text = last_resp.text.lower()
    if "face" not in page_text and "clientfacecheckstatus" not in page_text:
        return None

    self.gui.increment_metric("face_verification_required")
    self._thread_state.last_face_error = ""
    face_upload_succeeded = False
    for _ in range(10):
        if UploadFace(self, mobile, courseid, class_id, cpi, knowledge_id, session, puid):
            face_upload_succeeded = True
            break
    retry_params = {
        "clazzid": class_id,
        "courseid": courseid,
        "knowledgeid": knowledge_id,
        "num": num_candidates[0],
        "isPhone": 1,
        "control": "true",
        "cpi": cpi,
    }
    try:
        resp = _request_with_retry(
            self,
            session,
            "get",
            self.PAGE_MOBILE_CHAPTER_CARD,
            mobile,
            params=retry_params,
        )
    except requests.exceptions.RequestException as exc:
        self.gui.increment_metric("face_verification_failed")
        self.update_text.emit(
            f"{mobile} chapter card retry failed after face check: {context_tag}: {exc}"
        )
        return None
    attachment = _extract_attachment_setting(resp.text)
    if attachment is None:
        attachment = _extract_attachment_from_iframes(self, mobile, session, resp.text, resp.url)
    if attachment is None:
        attachment = extract_work_payload(resp.text, resp.url)
    if attachment is None:
        self.gui.increment_metric("face_verification_failed")
        reason = getattr(self._thread_state, "last_face_error", "")
        if face_upload_succeeded:
            self.update_text.emit(
                f"{mobile} face verification completed but attachment is still unavailable: {context_tag}"
            )
        else:
            suffix = f": {reason}" if reason else ""
            self.update_text.emit(f"{mobile} face verification failed for chapter {context_tag}{suffix}")
    return attachment


def _extract_attachment_setting(page_html):
    soup = BeautifulSoup(page_html, "lxml")
    for script_tag in soup.find_all("script"):
        script_text = script_tag.text or ""
        if "AttachmentSetting" not in script_text:
            continue

        match = re.search(r"window\.AttachmentSetting\s*=\s*(?P<json>\{[\s\S]*?\})\s*;", script_text)
        if not match:
            continue

        try:
            return json.loads(match.group("json"))
        except json.JSONDecodeError:
            continue
    return None


def _extract_attachment_from_iframes(self, mobile, session, page_html, base_url):
    soup = BeautifulSoup(page_html, "lxml")
    for iframe in soup.find_all("iframe"):
        iframe_src = (iframe.get("src") or "").strip()
        if not iframe_src:
            continue

        iframe_url = urljoin(base_url, iframe_src)
        try:
            iframe_resp = _request_with_retry(self, session, "get", iframe_url, mobile)
        except requests.exceptions.RequestException:
            continue

        attachment = _extract_attachment_setting(iframe_resp.text)
        if attachment is None:
            attachment = extract_work_payload(iframe_resp.text, iframe_url)
        if attachment is not None:
            return attachment
    return None


def _build_card_num_candidates(card_index, card):
    candidates = []

    def _add(value):
        try:
            parsed = int(value)
        except (TypeError, ValueError):
            return
        if parsed < 0:
            return
        if parsed not in candidates:
            candidates.append(parsed)

    _add(card_index)
    _add((card_index or 0) + 1)
    if isinstance(card, dict):
        card_order = card.get("cardorder")
        _add(card_order)
        try:
            card_order_int = int(card_order)
        except (TypeError, ValueError):
            card_order_int = None
        if card_order_int is not None and card_order_int > 0:
            _add(card_order_int - 1)
            _add(card_order_int + 1)

    if not candidates:
        return [0]
    return candidates


def _build_chapter_context(course_names, chapter_name, knowledge_id):
    course_text = str(course_names or "unknown-course").strip()
    chapter_text = str(chapter_name or "unknown-chapter").strip()
    knowledge_text = str(knowledge_id or "unknown-knowledge").strip()
    return f"{course_text}-{chapter_text}-{knowledge_text}"


def _build_point_payload(point):
    if point is None:
        return None

    payload = {}
    point_data = (point.get("data") or "").strip()
    if point_data:
        parsed = _parse_point_data(point_data)
        if isinstance(parsed, dict):
            payload.update(parsed)
        else:
            payload["data_raw"] = point_data

    point_src = (point.get("src") or "").strip()
    if point_src:
        payload["src"] = point_src

    return payload or None


def _parse_point_data(raw_data):
    for candidate in (raw_data, unescape(raw_data)):
        if not candidate:
            continue
        try:
            parsed = json.loads(candidate)
        except json.JSONDecodeError:
            continue
        if isinstance(parsed, dict):
            return parsed
    return None


def _resolve_exam_url(self, session, mobile, url):
    request_url = _normalize_chaoxing_url(url)
    if not request_url:
        return ""

    final_url = session.get(request_url, allow_redirects=True, timeout=5).url
    if "antispiderShowVerify" not in final_url:
        return final_url

    self.gui.increment_metric("anti_spider_verifies")
    if hasattr(self, "notify_backpressure"):
        self.notify_backpressure(mobile, "anti_spider_verify")
    for _ in range(10):
        if VerRunExam(self, mobile, session):
            final_url = session.get(request_url, allow_redirects=True, timeout=5).url
            if "antispiderShowVerify" not in final_url:
                return final_url
    return ""


def _fetch_exam_questions(self, session, course_id, class_id, exam_id, cpi, exam_answer_id, progress_callback):
    resp = session.get(
        self.API_EXAM_CONTENT,
        params={
            "courseId": course_id,
            "classId": class_id,
            "examId": exam_id,
            "cpi": cpi,
            "examAnswerId": exam_answer_id,
        },
        timeout=5,
    )
    resp.raise_for_status()
    question_link_ids = re.findall(r"""<li data="(.*?)">""", resp.text)
    questions = []

    for question_link_id in question_link_ids:
        detail_resp = session.get(
            self.API_EXAM_CONTENT,
            params={
                "courseId": course_id,
                "classId": class_id,
                "examId": exam_id,
                "examAnswerId": exam_answer_id,
                "isDetail": "true",
                "questionLinkId": question_link_id,
                "times": 0,
                "newVersion": 1,
            },
            timeout=5,
        )
        detail_resp.raise_for_status()
        question_data = _extract_exam_entry(self, BeautifulSoup(detail_resp.text, "html.parser"))
        if question_data is None:
            continue
        questions.append(question_data)
        progress_callback()

    return questions


def _extract_exam_entry(self, soup):
    title_node = soup.find("div", class_="tit")
    if title_node is None:
        return None

    raw_question_text = title_node.get_text(strip=True)
    question = raw_question_text
    for image in title_node.find_all("img"):
        src = image.get("src")
        if src:
            question += src

    quetype_node = title_node.find("span")
    quetype = quetype_node.get_text(strip=True) if quetype_node else "unknown"
    match = re.search(r"\((.*?)\)", quetype)
    if match:
        quetype = match.group(1)
    quetype = quetype.split(",")[0]

    options = " | ".join(
        [
            f"{item.get_text(strip=True)}{(item.find('img').get('src')) if item.find('img') else ''}"
            for item in soup.find_all("div", class_="optionCon")
        ]
    )
    answer = _extract_exam_answer(self, soup, raw_question_text, options)
    if not answer:
        return None

    cleaned_question = re.sub(r"\d+\.\(.*?\)|\[\w*?\]", "", question)
    return {
        "type": quetype,
        "question": cleaned_question,
        "options": options,
        "answer": answer,
    }


def _extract_exam_answer(self, soup, raw_question_text, options):
    answer_info_node = soup.find("div", class_="answerInfo")
    answer_info = answer_info_node.get_text(strip=True) if answer_info_node else ""
    green_node = soup.find("div", class_="greenColor")
    green_answer = green_node.get_text(strip=True) if green_node else ""
    score_node = soup.find("p", class_="score")
    score_text = score_node.get_text(strip=True) if score_node else ""

    question_score = _extract_last_number(raw_question_text)
    current_score = _extract_first_number(score_text)
    answer = ""

    if answer_info and question_score and current_score and question_score == current_score:
        answer = answer_info
    elif green_answer:
        answer = green_answer
    elif answer_info:
        answer = answer_info

    if not answer:
        return ""

    resultanswer = find_answers(self, options, answer)
    return re.sub(r"\((\d)\)\s*", "", resultanswer).strip()


def _extract_last_number(text):
    numbers = re.findall(r"\d+", text or "")
    return numbers[-1] if numbers else None


def _extract_first_number(text):
    numbers = re.findall(r"\d+", text or "")
    return numbers[0] if numbers else None


def _emit_exam_list_progress(self):
    self.emit_question_progress("exam", "3")


def _emit_exam_progress(self, collect_type):
    self.emit_question_progress("exam", collect_type)


def _build_homework_review_url(session, task_html, fallback_course_id, fallback_class_id):
    select_matches = re.findall(r"/work/phone/selectWorkQuestionYiPiYue(.*?)';", task_html)
    if select_matches:
        suffix = select_matches[0]
        base_url = _normalize_chaoxing_url(
            f"https://mooc1-api.chaoxing.com/mooc-ans/work/phone/selectWorkQuestionYiPiYue{suffix}"
        )
        if not base_url:
            return "", ""
        resp = session.get(base_url, timeout=3)
        resp.raise_for_status()
        question_ids = re.findall(r"""<dl data="(.*?)" role="none">""", resp.text)
        course_id = get_query_parameter(base_url, "courseId") or fallback_course_id
        class_id = get_query_parameter(base_url, "classId") or fallback_class_id
        work_answer_id = get_query_parameter(base_url, "workAnswerId")
        work_id = get_query_parameter(base_url, "workId")
        if not question_ids or not work_answer_id or not work_id:
            return "", ""

        question_link_id = question_ids[0]
        review_url = (
            "https://mooc1-api.chaoxing.com/mooc-ans/work/phone/selectWorkQuestionYiPiYue"
            f"?courseId={course_id}&classId={class_id}&workId={work_id}"
            f"&workAnswerId={work_answer_id}&questionLinkId={question_link_id}"
            "&status=4&isReport=false&mooc=1&times=0&isAccessibility=0&ut=s"
        )
        return review_url, question_link_id

    question_ids = re.findall(r"""<dl data="(.*?)" role="none">""", task_html)
    look_matches = re.findall(r"""function look\(([\s\S]*?)</script>""", task_html)
    work_ids = re.findall(r"""workId=(.*?)&""", look_matches[0]) if look_matches else []
    work_answer_ids = re.findall(r"""workAnswerId=(.*?)&""", look_matches[0]) if look_matches else []
    if not question_ids or not work_ids or not work_answer_ids:
        return "", ""

    question_link_id = question_ids[0]
    review_url = (
        "https://mooc1-api.chaoxing.com/mooc-ans/work/phone/selectWorkQuestionYiPiYue"
        f"?courseId={fallback_course_id}&classId={fallback_class_id}&workId={work_ids[0]}"
        f"&workAnswerId={work_answer_ids[0]}&questionLinkId={question_link_id}"
        "&status=4&isReport=false&mooc=1&times=0&isAccessibility=0&ut=s"
    )
    return review_url, question_link_id


def _extract_homework_entry(self, question_block):
    quetype_node = question_block.find("span", class_="stemGray")
    quetype = quetype_node.get_text(strip=True) if quetype_node else "unknown"
    match = re.search(r"\((.*?)\)", quetype)
    if match:
        quetype = match.group(1)
    quetype = quetype.split("(")[0]

    question_node = question_block.find("div", class_="Picdiv")
    if question_node is None:
        return None

    image_node = question_node.find("img")
    image_src = image_node.get("src") if image_node else ""
    question = f"{question_node.get_text(strip=True)}{image_src}"
    question = re.sub(r"^\d+\.\s*", "", question, count=1)
    question = re.sub(r"^\d+銆乗s*", "", question, count=1)

    option_div = question_block.find("div", class_="optionDiv")
    if option_div is None:
        return None

    options = " | ".join(
        [
            f"{item.get_text(strip=True)}{(item.find('img').get('src')) if item.find('img') else ''}"
            for item in option_div.find_all("div", class_="optionCon")
        ]
    )
    options = options.replace("//mooc1-api.chaoxing.com/mooc-ans/images/work/phone/right_v1.png", "")
    options = options.replace("//mooc1-api.chaoxing.com/mooc-ans/images/work/phone/wrong_v1.png", "")

    answer_parts = [
        re.sub(r"\((\d)\)\s*", "", item.get_text(strip=True))
        for item in question_block.find_all("p", class_="analysis_pcon greenColor")
    ]
    if answer_parts:
        answer = self.separator.join(answer_parts)
    else:
        answer_node = question_block.find("span", class_="greenColor")
        answer = answer_node.get_text(strip=True) if answer_node else ""

    if not answer:
        return None

    return {
        "type": quetype,
        "question": question,
        "options": options,
        "answer": find_answers(self, options, answer),
    }


def _emit_homework_progress(self, collect_type):
    self.emit_question_progress("home", collect_type)


def _normalize_chaoxing_url(url, base_url="https://mooc1-api.chaoxing.com"):
    normalized = unescape(str(url or "")).strip().strip("\"'")
    if not normalized:
        return ""

    parsed = urlparse(normalized)
    if parsed.scheme and parsed.scheme not in {"http", "https"}:
        return ""
    if parsed.scheme:
        return normalized
    return urljoin(base_url, normalized)


def get_query_parameter(url, param_name):
    try:
        normalized_url = unescape(str(url or "")).strip()
        parsed_url = urlparse(normalized_url)
        query_params = parse_qs(parsed_url.query)
        values = query_params.get(param_name) or []
        return values[0] if values else ""
    except Exception:
        return ""
