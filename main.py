import os
import subprocess
from app.utils import extract_audio_features, extract_visual_features

video_id = "Clip1"
base_path = f"custom_data/test/{video_id}"

video_path = f"{base_path}/{video_id}.mp4"
audio_path = f"{base_path}/audio.wav"
frame_path = f"{base_path}/frames"
lip_path = f"{base_path}/lip_frames"
visual_save_path = f"{base_path}/visual_features/{video_id}.pt"
audio_save_path = f"{base_path}/audio_features/{video_id}.pt"

# 1. Extract audio
os.makedirs(base_path, exist_ok=True)
subprocess.run([
    "ffmpeg", "-i", video_path, "-ar", "16000", "-ac", "1", audio_path, "-y"
])

# 2. Extract frames
os.makedirs(frame_path, exist_ok=True)
subprocess.run([
    "ffmpeg", "-i", video_path, "-vf", "fps=25", f"{frame_path}/frame_%05d.jpg", "-y"
])

# 3. Crop lips from frames using dlib
import cv2
import dlib
import numpy as np
from tqdm import tqdm

detector = dlib.get_frontal_face_detector()
predictor = dlib.shape_predictor("shape_predictor_68_face_landmarks.dat")


os.makedirs(lip_path, exist_ok=True)

for fname in tqdm(sorted(os.listdir(frame_path))):
    frame = cv2.imread(os.path.join(frame_path, fname))
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    faces = detector(gray)
    if len(faces) > 0:
        shape = predictor(gray, faces[0])
        lips = [(shape.part(n).x, shape.part(n).y) for n in range(48, 68)]
        lips = np.array(lips)
        x, y, w, h = cv2.boundingRect(lips)
        margin = 20
        x = max(x - margin, 0)
        y = max(y - margin, 0)
        w = w + 2 * margin
        h = h + 2 * margin
        crop = frame[y:y+h, x:x+w]
        resized = cv2.resize(crop, (88, 88))
        cv2.imwrite(os.path.join(lip_path, fname), resized)

# 4. Extract features
os.makedirs(os.path.dirname(visual_save_path), exist_ok=True)
extract_visual_features(
    lip_frame_dir=lip_path,
    save_path=visual_save_path,
    visual_frontend_path="pretrained/visual_frontend.pt"
)

os.makedirs(os.path.dirname(audio_save_path), exist_ok=True)
extract_audio_features(
    wav_path=audio_path,
    save_path=audio_save_path,
    hubert_model_path="pretrained/hubert_base_ls960.pt"
)
