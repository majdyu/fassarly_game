# Tournament Setup

## Start locally

```powershell
docker compose up -d --build
```

## URLs

- Game: http://localhost:8090/index.html
- Admin: http://localhost:8090/admin/login.php
- Participant: http://localhost:8090/participant/login.php
- phpMyAdmin: http://localhost:8091

## Default admin

- Login: `admin`
- Password: `admin1999`

## Notes

- The existing game flow is still available through `index.html`.
- Tournament results are saved only when the participant starts from the participant area and chooses tournament mode.
- Training mode does not save tournament results.
- Docker ports were set to `8090` and `8091` because `8080` and `8081` were already used locally.
