FZPKG (richiede bootstrap):

DA CARTELLA:

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

DA GITLAB:

- Aggiungere '"fuzzy/fzpkg": "dev-main"' alla sezione "require"
- Aggiungere

"repositories": [
    {
        "type": "vcs",
        "url": "git@gitlab.home.space:fuzzy/fzpkg.git"
    }
]