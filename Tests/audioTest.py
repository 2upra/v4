import essentia.standard as es

def test_essentia(audio_path):
    # Cargar el archivo de audio
    try:
        audio = es.MonoLoader(filename=audio_path)()
        print(f"Audio cargado correctamente. Longitud: {len(audio)} muestras.")
    except Exception as e:
        print(f"Error al cargar el audio: {e}")
        return

    # Extraer BPM usando RhythmExtractor2013
    try:
        rhythm_extractor = es.RhythmExtractor2013(method="multifeature")
        bpm, _, _, _, _ = rhythm_extractor(audio)
        print(f"BPM detectado: {bpm}")
    except Exception as e:
        print(f"Error al extraer el BPM: {e}")

    # Extraer pitch (tono) usando PredominantPitchMelodia
    try:
        pitch_extractor = es.PredominantPitchMelodia()
        pitch, _ = pitch_extractor(audio)
        print(f"Pitch (tono) detectado: {pitch[:10]}")  # Imprime los primeros 10 valores
    except Exception as e:
        print(f"Error al extraer el tono: {e}")

    # Extraer la clave musical usando KeyExtractor
    try:
        key_extractor = es.KeyExtractor()
        key, scale, strength = key_extractor(audio)
        print(f"Clave detectada: {key}, Escala: {scale}, Fuerza: {strength}")
    except Exception as e:
        print(f"Error al extraer la clave musical: {e}")

if __name__ == "__main__":
    import sys
    if len(sys.argv) != 2:
        print("Uso: python test_essentia.py <ruta_al_audio>")
    else:
        audio_path = sys.argv[1]
        test_essentia(audio_path)
