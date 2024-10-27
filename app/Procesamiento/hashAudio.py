import librosa
import numpy as np
import hashlib

def calcular_hash_audio(audio_path):
    try:
        # Cargar el archivo de audio
        y, sr = librosa.load(audio_path, sr=None)

        # Extraer el mel-spectrogram (representación de las características del audio)
        mel_spectrogram = librosa.feature.melspectrogram(y=y, sr=sr)

        # Tomar el logaritmo del mel-spectrogram para hacerlo más manejable
        log_mel_spectrogram = librosa.power_to_db(mel_spectrogram, ref=np.max)

        # Convertir el espectrograma a una representación en bytes
        mel_bytes = log_mel_spectrogram.tobytes()

        # Crear un hash usando SHA-256 (resistente a cambios menores)
        hash_obj = hashlib.sha256(mel_bytes)

        # Devolver el hash en formato hexadecimal
        return hash_obj.hexdigest()
    
    except Exception as e:
        # Si hay un error, manejarlo y retornar None
        print(f"Error al procesar el audio {audio_path}: {e}")
        return None