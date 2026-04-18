from hashlib import md5
import time

def fetch_face(self, puid, session):
    resp = session.get(
        self.API_FACE_IMAGE,
        params={
            "enc": md5(f"{puid}uWwjeEKsri".encode()).hexdigest(),
            "token": "4faa8662c59590c6f43ae9fe5b002b42",
            "_time": get_ts(),
        },
        timeout=3
    )
    resp.raise_for_status()
    json_content = resp.json()
    data = json_content.get("data") or {}
    if not isinstance(data, dict):
        return False
    url = data.get("http", "")
    if url:
        image_resp = session.get(url, timeout=3)
        image_resp.raise_for_status()
        with open(f"faces/{puid}.jpg", "wb") as f:
            f.write(image_resp.content)
        return True
    return False

def get_ts() -> str:
    return f"{round(time.time() * 1000)}"
