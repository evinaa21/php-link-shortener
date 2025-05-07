# PHP Link Shortener — Junior PHP Developer Test Task

This mini-project lets you create a short link using some parameters, and then you can look up the original info later using a special code.

---

## 📝 Overview

This project is a lightweight PHP system that:

- Accepts URL parameters (`keyword`, `src`, `creative`)
- Generates a unique internal code (`our_param`)
- Redirects to another website using only `our_param`
- Lets you retrieve the original parameters using `our_param`
- Supports refreshing the mapping and keeps a history

---

## ⚙️ How `our_param` is Generated

- The code combines `keyword`, `src`, and `creative` and makes a hash using `md5`. This hash is called `our_param`.
- If you use `refresh=1` in the URL, it makes a new unique hash for the same combination.

---

## 📦 How Data is Stored

- Mappings are saved in `data/mappings.txt` like this:
  ```
  our_param keyword src creative
  ```
- If you refresh, the old and new codes are saved in `data/history.txt` like this:
  ```
  old_param new_param
  ```

---

## 🔄 How the Refresh Mechanism Works

- If you add `refresh=1` to the URL, the system:
  1. Makes a new `our_param` for the same keyword, src, and creative.
  2. Saves the new mapping in `data/mappings.txt`.
  3. Logs the old and new codes in `data/history.txt` so you can track changes.

---

## 🚦 Request Handling & Missing Parameters

- If you don't give any parameters, it gives an error (HTTP 400).
- If you only give one or two, it fills in the rest with `"unknown"`.
- This is explained in the code comments.

---

## 🔒 Security Considerations

- Only allows letters, numbers, underscores, and dashes in input.
- Each parameter is limited to 64 characters.
- The `our_param` code is 32 characters.
- File access is locked to prevent problems if two people use it at once.
- File paths use `__DIR__` so you can't trick it into writing somewhere else.

---

## ⚡ Performance Considerations

- File locking is used for safety.
- For lots of users (like 1 million requests/day), you should use a database or split the files.

---

## 💡 Feature Extensions (Ideas)

- Add a page to see and manage all the mappings.
- Add an expiration date for mappings.
- Use a database for bigger projects.
- Add a login for special actions.

---

## 🧪 Testing Instructions

1. Start your local server (like XAMPP) and put the project in the `htdocs` folder.
2. Open your browser and go to:  
   `http://localhost/Task_Internship/index.html`

### Sample CURL Commands

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

## 📁 What Each File Does

- `redirect.php` — Handles creating and refreshing short links.
- `retrieve.php` — Lets you look up the original info from a short code.
- `data/mappings.txt` — Stores all the mappings.
- `data/history.txt` — Stores the history of refreshed codes.
- `index.html` — Simple web page to use the system.
- `style.css` — Makes the web page look nice.

---

_Made for a junior developer test task!_
