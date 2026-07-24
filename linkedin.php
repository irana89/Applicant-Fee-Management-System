<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Mobile Menu Test</title>
<style>
/* Hide the checkbox input */
.menu-toggle {
  display: none;
}

/* Hamburger icon styling */
.menu-icon {
  display: none; /* hidden by default for desktop */
  cursor: pointer;
  width: 30px;
  height: 22px;
  position: relative;
  z-index: 1001;
  margin: 15px;
}

.menu-icon span {
  display: block;
  height: 4px;
  background: #0077b5;
  margin-bottom: 5px;
  border-radius: 2px;
  transition: 0.3s;
}

/* Desktop menu styling */
.menu {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0 20px;
}

.menu li {
  margin: 0 15px;
}

.menu li a {
  text-decoration: none;
  color: #222;
  padding: 10px 0;
  display: block;
  font-weight: 600;
  transition: color 0.3s;
}

.menu li a:hover {
  color: #0077b5;
}

/* Responsive styles */
@media (max-width: 900px) {
  /* Show hamburger */
  .menu-icon {
    display: block;
  }

  /* Hide the desktop menu by default on mobile */
  .menu {
    flex-direction: column;
    position: fixed;
    top: 0;
    left: -260px; /* hide off screen */
    width: 240px;
    height: 100vh;
    background: #fff;
    padding-top: 60px;
    margin: 0;
    box-shadow: 2px 0 12px rgba(0, 0, 0, 0.15);
    overflow-y: auto;
    transition: left 0.3s ease;
    z-index: 1000;
  }

  .menu li {
    margin: 0;
  }

  .menu li a {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
  }

  /* Show menu when toggle is checked */
  .menu-toggle:checked ~ .menu {
    left: 0;
  }

  /* Hamburger toggle animation */
  .menu-toggle:checked + .menu-icon span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
  }

  .menu-toggle:checked + .menu-icon span:nth-child(2) {
    opacity: 0;
  }

  .menu-toggle:checked + .menu-icon span:nth-child(3) {
    transform: rotate(-45deg) translate(5px, -5px);
  }
}

</style>
</head>
<body>
<nav id="site-navigation" class="main-navigation">
  <input type="checkbox" id="menu-toggle" class="menu-toggle" />
  <label for="menu-toggle" class="menu-icon" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
  </label>
  <ul id="pbmit-top-menu" class="menu">
    <li><a href="#">Home</a></li>
    <li><a href="#">About Us</a></li>
    <li><a href="#">Services</a></li>
    <li><a href="#">Contact</a></li>
  </ul>
</nav>
</body>
</html>
