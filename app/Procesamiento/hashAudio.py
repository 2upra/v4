# hashAudio.py
import os
import librosa
import numpy as np
import hashlib
import sys

def calcular_hash_audio(audio_path):
    try:
        # Verificar si el archivo existe
        if not os.path.exists(audio_path):
            sys.stderr.write(f"Archivo no encontrado: {audio_path}\n")
            return None

        # Cargar el archivo
        try:
            y, sr = librosa.load(audio_path, sr=None)
        except Exception as load_error:
            sys.stderr.write(f"Error cargando el archivo de audio: {load_error}\n")
            return None

        # Procesar el audio
        mel_spectrogram = librosa.feature.melspectrogram(y=y, sr=sr)
        log_mel_spectrogram = librosa.power_to_db(mel_spectrogram, ref=np.max)
        mel_bytes = log_mel_spectrogram.tobytes()
        hash_obj = hashlib.sha256(mel_bytes)
        
        return hash_obj.hexdigest()

    except Exception as e:
        sys.stderr.write(f"Error en el procesamiento: {e}\n")
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.stderr.write("Error: Se requiere la ruta del archivo de audio como argumento\n")
        sys.exit(1)
    
    audio_path = sys.argv[1]
    hash_result = calcular_hash_audio(audio_path)
    
    if hash_result:
        # Solo imprimir el hash, nada más
        print(hash_result)
    else:
        sys.exit(1)