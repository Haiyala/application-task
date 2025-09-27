# Applicatio Task - Restful API

## How to run (Codespace)
1. Open this repo in GitHub Codespaces.
2. Codespace runs Apache + PHP. Open forwarded port 80 in the browser preview.
3. Visit `http://localhost:80` -> the frontend is at `/application-task/index.html`.
4. Fill the form and click *Get Rates*. This posts to `/application-task/api/rates.php`.

## Files
- `application-task/api/rates.php` - backend proxy (the REST endpoint)
- `application-task/index.html` & `public/js/app.js` - frontend
- `.github/workflows/sonarcloud.yml` - GitHub Action for SonarCloud
- `.devcontainer/*` - Codespaces config
