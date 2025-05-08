# PHP Link Shortener — Junior PHP Developer Test Task

This project is a simple PHP-based link shortener. It allows users to generate a short code from a set of parameters and later retrieve the original information using that code.

---

## Overview

The application is designed to:

- Accept three URL parameters: `keyword`, `src`, and `creative`
- Generate a unique code (`our_param`) based on these parameters
- Redirect users using only the generated code
- Allow retrieval of the original parameters using the code
- Support refreshing the mapping and keeping a history of changes

---

## How `our_param` is Generated

The `our_param` code is created by combining the `keyword`, `src`, and `creative` values and hashing them with the `md5` algorithm. If the `refresh=1` parameter is included in the URL, a new unique hash is generated even for the same combination.

---

## Data Storage

- Mappings are stored in `data/mappings.txt` in the following format:
  ```
  our_param keyword src creative
  ```
- When a mapping is refreshed, the old and new codes are saved in `data/history.txt`:
  ```
  old_param new_param
  ```

---

## Refresh Mechanism

If the `refresh=1` parameter is present:

1. A new `our_param` is generated for the same set of parameters.
2. The new mapping is saved in `data/mappings.txt`.
3. The change is logged in `data/history.txt` for tracking.

---

## Request Handling

- If no parameters are provided, the script returns an HTTP 400 error.
- If only one or two parameters are given, the missing ones are set to `"unknown"`.

---

## Security

- Only letters, numbers, underscores, and dashes are allowed in input.
- Each parameter is limited to 64 characters.
- The generated code is always 32 characters.
- File access uses locking to prevent conflicts.
- File paths use `__DIR__` for safety.

---

## Performance

- File locking is used to ensure safe concurrent access.
- For high-traffic scenarios, a database is recommended.

---

## Possible Extensions

- Add a management page for all mappings.
- Implement expiration dates for mappings.
- Switch to a database for scalability.
- Add authentication for special actions.

---

## Testing

1. Start your local server (e.g., XAMPP) and place the project in the `htdocs` folder.
2. Open your browser and go to:  
   `http://localhost/Task_Internship/index.html`

### Example CURL Commands

**Generate a link:**

```
curl "http://localhost/Task_Internship/redirect.php?keyword=test&src=google&creative=ad1"
```

**Refresh a link:**

```
curl "http://localhost/Task_Internship/redirect.php?keyword=test&src=google&creative=ad1&refresh=1"
```

**Retrieve original parameters:**

```
curl "http://localhost/Task_Internship/retrieve.php?our_param=YOUR_PARAM"
```

---

## File Descriptions

- `redirect.php` — Handles creation and refreshing of short links.
- `retrieve.php` — Retrieves the original parameters from a short code.
- `data/mappings.txt` — Stores all mappings.
- `data/history.txt` — Stores the history of refreshed codes.
- `index.html` — Web interface for the system.
- `style.css` — Styles for the web interface.

---

_Made as part of a junior PHP developer test task._
