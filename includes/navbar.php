```php
<div class="content">

<nav class="navbar navbar-light bg-white shadow-sm px-4">

<div>

<h5 class="mb-0">

Bienvenue,

<?= htmlspecialchars($fullname) ?>

</h5>

</div>

<div>

<span class="badge bg-success">

<?= strtoupper(htmlspecialchars($role)) ?>

</span>

</div>

</nav>

<div class="container-fluid mt-4">
```
