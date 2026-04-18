import os

import cv2
import numpy as np

from utils.faceutil import get_ts

def UploadFace(self, mobile, courseid, class_id, cpi, knowledge_id, session, puid):
    try:
        face_img = f"faces/{puid}.jpg"
        if os.path.exists(face_img):
            facetoken = get_face_upload_token(self, session)
            if not facetoken:
                self._thread_state.last_face_error = "upload token unavailable"
                return False
            face_img = cv2.imread(str(face_img))
            if face_img is None:
                self._thread_state.last_face_error = "cached face image unreadable"
                return False
            img_h, img_w, _ = face_img.shape
            rng = np.random.default_rng()
            for _ in range(rng.integers(0, 5)):
                face_img[
                    rng.integers(0, img_h - 1),
                    rng.integers(0, img_w - 1),
                    rng.integers(0, 2),
                ] += rng.integers(-2, 2)
            _, face_img_data = cv2.imencode(".jpg", face_img)
            resp = session.post(
                self.API_UPLOAD_FACE,
                params={
                    "uploadtype": "face",
                    "_token": facetoken,
                    "puid": puid,
                },
                files={
                    "file": (f"{get_ts()}.jpg", face_img_data, "image/jpeg"),
                },
                timeout=5,
            )
            json_content = resp.json()
            if json_content.get("result") is not True:
                self._thread_state.last_face_error = json_content.get("msg") or "upload rejected"
                return False
            else:
                object_id = json_content["objectId"]
                resp = session.get(
                    self.API_FACE_SUBMIT_INFO_NEW,
                    params={
                        "courseId": courseid,
                        "clazzId": class_id,
                        "cpi": cpi,
                        "chapterId": knowledge_id,
                        "objectId": object_id,
                        "type": 1,
                    },
                    timeout=5,
                )
                json_content = resp.json()
                if json_content.get("status") is not True:
                    self._thread_state.last_face_error = json_content.get("msg") or "validation rejected"
                    return False
                else:
                    self._thread_state.last_face_error = ""
                    return True
        else:
            self._thread_state.last_face_error = "cached face image not found"
            return False
    except Exception as exc:
        self._thread_state.last_face_error = str(exc)
        return False

def get_face_upload_token(self, session):
    resp = session.get(self.API_GET_PAN_TOKEN, timeout=5)
    json_content = resp.json()
    if json_content.get("result") is not True:
        return None
    else:
        facetoken = json_content["_token"]
        return facetoken
