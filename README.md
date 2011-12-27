Productive Birds
================

A very bare-bones project time tracking application for small teams described in
[a detailed blog post](http://mike.teczno.com/notes/angry-productive-birds.html).
There’s not a lot of tooling here, and the goal isn’t so much a web application
as an information display that can be tacked to the wall for high visibility.

![Example](http://mike.teczno.com/img/angry-birds/on-target.png)

Install
-------

1. Make a new MySQL database using `create.mysql`.
2. Set your database parameters in `connect_mysql()` at the top of `lib.php`.
3. Add a client to the `client_info` table (sorry, I haven’t made a form for any of this):
  * `client` column is their name.
  * `ends` is a date for when the project ends, formatted like `"YYYY-MM-DD"`.
  * `days` is the number of days your team can spend on the project based on the budget.
  * `budget` is an amount of money, used only for display and sorting.
4. Ignore the `same_clients` tables unless you’re like us and used to use lots of different names for the same things.
5. Modify the `PEOPLE` list at the top of `lib.php` with initials for your group.
6. Go to `record.php` and type in some times!
7. Get data out for use in other systems at `data.php`.
