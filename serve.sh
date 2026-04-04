#!/bin/bash
export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"
cd "/Users/drahmedkhaleel/Documents/Aura Derma Clinc/website"
exec php artisan serve --port=8000
