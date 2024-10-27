import os
import librosa
import numpy as np
import hashlib

def calcular_hash_audio(audio_path):
    try:
        # Verificar si el archivo existe
        if not os.path.exists(audio_path):
            print(f"Archivo no encontrado: {audio_path}")
            return None

        # Cargar el archivo de audio
        y, sr = librosa.load(audio_path, sr=None)

        # Extraer el mel-spectrogram
        mel_spectrogram = librosa.feature.melspectrogram(y=y, sr=sr)

        # Tomar el logaritmo
        log_mel_spectrogram = librosa.power_to_db(mel_spectrogram, ref=np.max)

        # Convertir a bytes y calcular el hash
        mel_bytes = log_mel_spectrogram.tobytes()
        hash_obj = hashlib.sha256(mel_bytes)

        return hash_obj.hexdigest()
    
    except Exception as e:
        print(f"Error al procesar el audio {audio_path}: {e}")
        return None
