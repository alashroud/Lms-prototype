# LMS Prototype – Feature Overview

This document lists the key features in the prototype and points to the code that implements each flow.
**Identifier naming note:** The schema’s book identifier (ISBN) column is named `IBSN` in code due to a legacy typo; snippets mirror the current implementation.

## Entry routing and layout
```php
// public/index.php
$view = (isset($_GET['q']) && $_GET['q'] != '') ? $_GET['q'] : '';
switch ($view) {
  case 'find':        $content = 'filterBooks.php'; break;
  case 'books':       $content = 'book.php'; break;
  case 'bookdetails': $content = 'single-view.php'; break;
  case 'borrow':      $content = 'borrow.php'; break;
  case 'checkout':    $content = 'checkout.php'; break;
  case 'login':       $content = 'login.php'; break;
  case 'announcements': $content = 'announcements.php'; break;
  case 'about':       $content = 'about.php'; break;
  case 'contact':     $content = 'contact.php'; break;
  default:            $content = 'home.php';
}
```

## Home page highlights and carousel
Displays announcements plus available books pulled from the catalog.
```php
// public/home.php
$mydb->setQuery("SELECT * FROM `tblbooks` WHERE Status='Available' GROUP BY BookTitle");
foreach ($mydb->loadResultlist() as $result) {
    $coverImage = "asset/images/covers/" . $result->IBSN . ".jpg";
    if (!file_exists($coverImage)) { $coverImage = "asset/images/covers/default.jpg"; }
    echo '<a href="index.php?q=borrow&id=' . $result->IBSN . '">';
    // ...
}
```
*Note:* The current query groups by `BookTitle` to suppress duplicates; switch to `DISTINCT` or add aggregates if you need deterministic rows.

## Catalog search with filtering
Borrowers can search by title, category, author, publisher, and published date with sanitized inputs. The current `public/filterBooks.php` sanitizes each field and assembles a dynamic WHERE clause.
**Warning:** The live code concatenates SQL strings; use a prepared statement like the example below instead of copying the concatenated pattern.
```php
// Safer pattern (conceptual):
$sanitizeInput = function ($value) use ($mydb) {
    return $mydb->escape_value(strip_tags(trim($value ?? '')));
};
$title = $sanitizeInput($_POST['BookTitle'] ?? '');
$category = $sanitizeInput($_POST['Category'] ?? '');
$author = $sanitizeInput($_POST['Author'] ?? '');
$publisher = $sanitizeInput($_POST['BookPublisher'] ?? '');
$publisheddate = $sanitizeInput($_POST['PublishDate'] ?? '');
$stmt = $mydb->prepare(
  "SELECT * FROM `tblbooks`
   WHERE Status = ?
     AND BookTitle LIKE ?
     AND Category LIKE ?
     AND Author LIKE ?
     AND BookPublisher LIKE ?
     AND PublishDate LIKE ?"
);
$stmt->execute([
  'Available',
  "%{$title}%",
  "%{$category}%",
  "%{$author}%",
  "%{$publisher}%",
  "%{$publisheddate}%"
]);
```

## Borrow request and checkout handoff
Shows selected book details and captures borrower info before posting to the checkout process handler.
```php
// public/borrow.php
$book = new Book();
$object = $book->single_books($_GET['id']);
$autonumber = new Autonumber();
$auto = $autonumber->set_autonumber("BorrowerID");
...
<form method="POST">
  <input type="hidden" name="id" value="<?php echo $id;?>">
  <input id="BorrowerId" name="BorrowerId" value="<?php echo DATE('Y').$auto->AUTO; ?>" readonly>
  <!-- borrower name/contact fields -->
</form>
```

## Borrower authentication
The login handler (`public/login.php`) currently hashes passwords with `sha1(trim($_POST['password']))` before checking credentials.
**Warning:** Treat that as legacy behavior only; replace it with the `password_hash()` / `password_verify()` approach below for any real use.
```php
// Safer pattern (conceptual):
$hash = password_hash($password, PASSWORD_DEFAULT);
if (password_verify($inputPassword, $hash)) {
    // authenticated
}
```

## Contact form with message logging
Contact submissions are posted via fetch and appended to log files for later review.
```php
// public/send_message.php
$output  = "--- NEW MESSAGE ---\n";
$output .= "Type: " . $type . "\n";
$output .= "Name: " . $name . "\n";
$output .= "Email: " . $email . "\n";
$output .= "Phone: " . $phone . "\n";
$output .= "Message: " . $message . "\n";
$output .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
$appendLog($contactLog, $output);
if ($type === 'Admin') { $appendLog($adminLog, $output); }
```

## Borrower self-service dashboard
Authenticated borrowers can view/update their profile, borrowed books, and password.
```php
// public/borrower/profile.php
$borrower = new Borrower();
$res = $borrower->single_borrower($_SESSION['BorrowerId']);
$view = $_GET['view'] ?? '';
switch ($view) {
  case 'borrowedbooks': $bContent = 'borrowedBooks.php'; break;
  case 'changepassword': $bContent = 'changepassword.php'; break;
  case 'view': $bContent = 'view.php'; break;
  default: $bContent = 'personalInfo.php';
}
require_once $bContent;
```

## Admin book management
Administrators can list, add, edit, and delete catalog items from the admin panel.
```php
// public/admin/books/list.php
$mydb->setQuery("SELECT * FROM `tblbooks`");
foreach ($mydb->loadResultlist() as $result) {
  echo '<td>' . $result->IBSN . '</td>';
  echo '<td>' . $result->BookTitle . '</td>';
  echo '<td>' . $result->Status . '</td>';
  $btn = '<a href="index.php?view=edit&id='.$result->IBSN.'" class="btn btn-primary btn-sm">Edit</a>';
  // ...
}
```
