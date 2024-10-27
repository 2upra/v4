import librosa
import numpy as np
import hashlib
import sys

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
        # Si hay un error, imprimirlo
        print(f"Error al procesar el audio {audio_path}: {e}", file=sys.stderr)
        return None

if __name__ == "__main__":
    # Asegurarse de que se ha pasado una ruta de archivo como argumento
    if len(sys.argv) < 2:
        print("Uso: python3 hashAudio.py <ruta_del_archivo>")
        sys.exit(1)

    # Obtener la ruta del archivo de audio desde los argumentos
    audio_path = sys.argv[1]

    # Calcular el hash del archivo
    hash_resultado = calcular_hash_audio(audio_path)

    if hash_resultado:
        # Si se ha calculado el hash, imprimirlo
        print(hash_resultado)
    else:
        print("No se pudo calcular el hash.", file=sys.stderr)