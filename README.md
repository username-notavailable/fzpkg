FZPKG (richiede bootstrap):

- Aggiungere '"fuzzy/fzpkg": "@dev"' alla sezione "require"
- Aggiungere

"repositories": [
    {
        "type": "path",
        "url": "/home/fuzzy/Documenti/path/Laravel/fzpkg",
        "options": {
            "symlink": true
        }
    }
]

al file composer.json (l'url è la path della cartella)