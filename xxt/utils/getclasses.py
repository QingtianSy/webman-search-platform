def GetClasses(self, session):
    try:
        resp = session.get(self.API_CLASS_LST, timeout=3)
        resp.raise_for_status()
        json_content = resp.json()
    except Exception as exc:
        self.update_text.emit(f"class list request failed: {exc}")
        return False

    if json_content.get("msg") != "获取成功":
        self.update_text.emit(f"class list response unexpected: {json_content.get('msg')}")
        return False

    extracted_info = []
    skipped_items = 0
    for item in json_content.get("channelList") or []:
        if item.get("cataName") != "课程":
            continue

        try:
            content = item.get("content") or {}
            course_list = (content.get("course") or {}).get("data") or []
            if not course_list:
                skipped_items += 1
                continue

            course_data = course_list[0] or {}
            course_id = course_data.get("id")
            course_name = course_data.get("name")
            class_id = content.get("id")
            cpi = item.get("cpi")
            key = item.get("key")
            if not all([course_id, course_name, class_id, cpi, key]):
                skipped_items += 1
                continue

            extracted_info.append(
                {
                    "id": course_id,
                    "class_id": class_id,
                    "cpi": cpi,
                    "key": key,
                    "course_names": course_name,
                }
            )
        except Exception:
            skipped_items += 1

    if skipped_items:
        self.update_text.emit(f"class list skipped {skipped_items} malformed item(s)")

    return extracted_info
