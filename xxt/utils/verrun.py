import numpy as np
import cv2
import time
from ddddocr import DdddOcr
import onnxruntime as ort

ort.set_default_logger_severity(3)
ocr = DdddOcr(show_ad=False)
det = DdddOcr(det=False, ocr=False, show_ad=False)
def VerRun(self, mobile, session):
    try:
        resp = session.get(self.API_CAPTCHA_IMG, params={"t": round(time.time() * 1000)}, timeout=5)
        content_type = resp.headers.get("Content-Type", "").lower()
        if not resp.ok or "image/png" not in content_type:
            return False
        else:
            content = resp.content
            img = np.frombuffer(content, np.uint8)
            img = cv2.imdecode(img, cv2.IMREAD_GRAYSCALE)
            _, img = cv2.threshold(img, 190, 255, cv2.THRESH_BINARY)
            img = cv2.bitwise_not(img)
            kernal = np.ones([3, 2], np.uint8)
            img = cv2.dilate(img, kernal, iterations=1)
            _, img_data = cv2.imencode(".png", img)
            code = ocr.classification(img_data.tobytes())
            resp = session.post(
                self.API_CAPTCHA_SUBMIT,
                data={
                    "app": 0,
                    "ucode": code,
                },
                allow_redirects=False,
                timeout=5,
            )
            if resp.status_code == 302:
                return True
            else:
                return False
    except Exception as e:
        return False

def VerRunExam(self, mobile, session):
    try:
        resp = session.get(self.API_CAPTCHA_IMG_EXAM, params={"t": round(time.time() * 1000)}, timeout=5)
        content_type = resp.headers.get("Content-Type", "").lower()
        if not resp.ok or "image/png" not in content_type:
            return False
        else:
            content = resp.content
            img = np.frombuffer(content, np.uint8)
            img = cv2.imdecode(img, cv2.IMREAD_GRAYSCALE)
            _, img = cv2.threshold(img, 190, 255, cv2.THRESH_BINARY)
            img = cv2.bitwise_not(img)
            kernal = np.ones([3, 2], np.uint8)
            img = cv2.dilate(img, kernal, iterations=1)
            _, img_data = cv2.imencode(".png", img)
            code = ocr.classification(img_data.tobytes())
            resp = session.post(
                self.API_CAPTCHA_SUBMIT_EXAM,
                data={
                    "app": 0,
                    "ucode": code,
                },
                allow_redirects=False,
                timeout=5,
            )
            if resp.status_code == 302:
                return True
            else:
                return False
    except Exception as e:
        return False

def VerCaptchaLogin(cutout_image_base64, shade_image_base64):
    res = det.slide_match(cutout_image_base64, shade_image_base64)
    targets = (res or {}).get("target") or []
    if not targets:
        raise ValueError("slide target missing")
    results = targets[0]
    return results
