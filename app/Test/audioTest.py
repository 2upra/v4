import essentia.standard as es

def test_audio_loading(audio_path):
    try:
        audio = es.MonoLoader(filename=audio_path)()
        print(f"Audio cargado correctamente. Longitud: {len(audio)} muestras.")
    except Exception as e:
        print(f"Error al cargar el audio: {e}")

if __name__ == "__main__":
    import sys
    audio_path = sys.argv[1]
    test_audio_loading(audio_path)