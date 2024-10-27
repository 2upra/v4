#!/bin/bash

# Establecer el entorno Python
export PYTHONPATH=/usr/local/lib/python3.9/dist-packages:/usr/lib/python3/dist-packages
export PATH=/usr/local/bin:/usr/bin:$PATH

# Ejecutar el script Python
/usr/bin/python3 /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/hashAudio.py "$1"
