<?php 
session_start();

if (!isset($_SESSION['login_status'])) {
  header('location:admin_login.php?msg=1');
}

?>
<!DOCTYPE html>

<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Boxicons CSS -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <!-- <title>Admin Dashboard</title> -->
    
    <style>
        /* Import Google font - Poppins */
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}
:root {
  --white-color: #fff;
  --blue-color: #4070f4;
  --grey-color: #707070;
  --grey-color-light: #aaa;
}
body {
  background-color: #e7f2fd;
  transition: all 0.5s ease;
}
body.dark {
  background-color: #333;
}
body.dark {
  --white-color: #333;
  --blue-color: #fff;
  --grey-color: #f2f2f2;
  --grey-color-light: #aaa;
}

/* navbar */
.navbar {
  position: fixed;
  top: 0;
  width: 100%;
  left: 0;
  background-color: var(--white-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 30px;
  z-index: 1000;
  box-shadow: 0 0 2px var(--grey-color-light);
}
.logo_item {
  display: flex;
  align-items: center;
  column-gap: 10px;
  font-size: 22px;
  font-weight: 500;
  color: var(--blue-color);
}
.navbar img {
  width: 35px;
  height: 35px;
  border-radius: 50%;
}
.search_bar {
  height: 47px;
  max-width: 430px;
  width: 100%;
}
.search_bar input {
  height: 100%;
  width: 100%;
  border-radius: 25px;
  font-size: 18px;
  outline: none;
  background-color: var(--white-color);
  color: var(--grey-color);
  border: 1px solid var(--grey-color-light);
  padding: 0 20px;
}
.navbar_content {
  display: flex;
  align-items: center;
  column-gap: 25px;
}
.navbar_content i {
  cursor: pointer;
  font-size: 20px;
  color: var(--grey-color);
}

/* sidebar */
.sidebar {
  background-color: var(--white-color);
  width: 260px;
  position: fixed;
  top: 0;
  left: 0;
  height: 100%;
  padding: 80px 20px;
  z-index: 100;
  overflow-y: scroll;
  box-shadow: 0 0 1px var(--grey-color-light);
  transition: all 0.5s ease;
}
.sidebar.close {
  padding: 60px 0;
  width: 80px;
}
.sidebar::-webkit-scrollbar {
  display: none;
}
.menu_content {
  position: relative;
}
.menu_title {
  margin: 15px 0;
  padding: 0 20px;
  font-size: 18px;
}
.sidebar.close .menu_title {
  padding: 6px 30px;
}
.menu_title::before {
  color: var(--grey-color);
  white-space: nowrap;
}
/* .menu_dahsboard::before {
  content: "Home";
} */
.menu_editor::before {
  content: "Editor";
}
.menu_setting::before {
  content: "Setting";
}
.sidebar.close .menu_title::before {
  content: "";
  position: absolute;
  height: 2px;
  width: 18px;
  border-radius: 12px;
  background: var(--grey-color-light);
}
.menu_items {
  padding: 0;
  list-style: none;
}
.navlink_icon {
  position: relative;
  font-size: 22px;
  min-width: 50px;
  line-height: 40px;
  display: inline-block;
  text-align: center;
  border-radius: 6px;
}
.navlink_icon::before {
  content: "";
  position: absolute;
  height: 100%;
  width: calc(100% + 100px);
  left: -20px;
}
.navlink_icon:hover {
  background: var(--blue-color);
}
.sidebar .nav_link {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 4px 15px;
  border-radius: 8px;
  text-decoration: none;
  color: var(--grey-color);
  white-space: nowrap;
}
.sidebar.close .navlink {
  display: none;
}
.nav_link:hover {
  color: var(--white-color);
  background: var(--blue-color);
}
.sidebar.close .nav_link:hover {
  background: var(--white-color);
}
.submenu_item {
  cursor: pointer;
}
.submenu {
  display: none;
}
.submenu_item .arrow-left {
  position: absolute;
  right: 10px;
  display: inline-block;
  margin-right: auto;
}
.sidebar.close .submenu {
  display: none;
}
.show_submenu ~ .submenu {
  display: block;
}
.show_submenu .arrow-left {
  transform: rotate(90deg);
}
.submenu .sublink {
  padding: 15px 15px 15px 52px;
}
.bottom_content {
  position: fixed;
  bottom: 60px;
  left: 0;
  width: 260px;
  cursor: pointer;
  transition: all 0.5s ease;
}
.bottom {
  position: absolute;
  display: flex;
  align-items: center;
  left: 0;
  justify-content: space-around;
  padding: 18px 0;
  text-align: center;
  width: 100%;
  color: var(--grey-color);
  border-top: 1px solid var(--grey-color-light);
  background-color: var(--white-color);
}
.bottom i {
  font-size: 20px;
}
.bottom span {
  font-size: 18px;
}
.sidebar.close .bottom_content {
  width: 50px;
  left: 15px;
}
.sidebar.close .bottom span {
  display: none;
}
.sidebar.hoverable .collapse_sidebar {
  display: none;
}
#sidebarOpen {
  display: none;
}
@media screen and (max-width: 768px) {
  #sidebarOpen {
    font-size: 25px;
    display: block;
    margin-right: 10px;
    cursor: pointer;
    color: var(--grey-color);
  }
  .sidebar.close {
    left: -100%;
  }
  .search_bar {
    display: none;
  }
  .sidebar.close .bottom_content {
    left: -100%;
  }
}
    </style>
  </head>
  <body>
    <!-- navbar -->
    <nav class="navbar">
      <div class="logo_item">
        <i class="bx bx-menu" id="sidebarOpen"></i>
        <h2 style="color:green">Medlife</h2>
        
      </div>

      <div class="search_bar">
        <input type="text" placeholder="Search" />
      </div>

      <div class="navbar_content">
        <i class="bi bi-grid"></i>
        <i class='bx bx-sun' id="darkLight"></i>
        <a href="admin_logout.php" style="color:red ; text-decoration:none">Logout</a>
        <p style="color:green">Welcome <?php echo $_SESSION['name'] ?>
        
      </div>
    </nav>

    
    <nav class="sidebar">
      <div class="menu_content">
        <ul class="menu_items">
          <div class="menu_title menu_dahsboard">
          <li class="item">
            <ul>
              <a href="admin_home.php" class="nav_link sublink" >Dashboard</a>
            </ul>
          </li>
          </div>
          
          <!-- duplicate or remove this li tag if you want to add or remove navlink with submenu -->
          <!-- start -->
          <!-- <li class="item">
            <div  class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bx-home-alt"></i>
              </span>
              <ul class="navlink" class="menu_items submenu">
                <a href="admin_home.php" class="nav_link sublink"> Home</a>
              </ul>
              
            </div>

            <ul class="menu_items submenu">
              <a href="#" class="nav_link sublink">Nav Sub Link</a>
              
            </ul>
          </li>  -->
          <!-- end -->

          <!-- duplicate this li tag if you want to add or remove  navlink with submenu -->
          <!-- start -->
          <!-- <li class="item">
            <ul>
              <a href="admin_home.php" class="nav_link sublink" class="menu_title menu_dahsboard">Home</a>
            </ul>
          </li> -->


          <li class="item">
            <div href="" class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bx-category-alt"></i>
              </span>
              <span class="navlink">Categories</span>
              <i class="bx bx-chevron-right arrow-left"></i>
            </div>

            <ul class="menu_items submenu">
            <a href="categories.php" class="nav_link sublink">Add Categories</a>
              <a href="viewcat.php" class="nav_link sublink">View Categories</a>
              
              
            </ul>
          </li>
          <li class="item">
            <div href="#" class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bxs-component"></i>
              </span>
              <span class="navlink">Products</span>
              <i class="bx bx-chevron-right arrow-left"></i>
            </div>

            <ul class="menu_items submenu">
              <a href="products.php" class="nav_link sublink">Add Products</a>
              <a href="view_products.php" class="nav_link sublink">Manage Products</a>
              
            </ul>
          </li>
          <li class="item">
            <div href="#" class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bx-capsule"></i>
              </span>
              <span class="navlink">Medicines</span>
              <i class="bx bx-chevron-right arrow-left"></i>
            </div>

            <ul class="menu_items submenu">
              <a href="view_medicines.php" class="nav_link sublink">Manage Medicines</a>
              
              
            </ul>
          <li class="item">
            <div href="#" class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bx-bone"></i>
              </span>
              <span class="navlink">Supplements</span>
              <i class="bx bx-chevron-right arrow-left"></i>
            </div>

            <ul class="menu_items submenu">
              <a href="view_supplements.php" class="nav_link sublink">Manage Supplements</a>
              
              
            </ul>
            <li class="item">
            <div href="#" class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bx-tachometer"></i>
              </span>
              <span class="navlink">Devices</span>
              <i class="bx bx-chevron-right arrow-left"></i>
            </div>

            <ul class="menu_items submenu">
              <a href="view_devices.php" class="nav_link sublink">Manage Devices</a>
              
              
            </ul>
          </li>
          </li>
          
          <li class="item">
            <div href="" class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bx-cart"></i>
              </span>
              <span class="navlink">Order</span>
              <i class="bx bx-chevron-right arrow-left"></i>
            </div>

            <ul class="menu_items submenu">
              <a href="admin_order.php" class="nav_link sublink">View Order</a>
              <a href="#" class="nav_link sublink">Update Order</a>
              
            </ul>
          </li>
          <li class="item">
            <div href="" class="nav_link submenu_item">
              <span class="navlink_icon">
                <i class="bx bx-user"></i>
              </span>
              <span class="navlink">Account</span>
              <i class="bx bx-chevron-right arrow-left"></i>
            </div>

            <ul class="menu_items submenu">
              <a href="admin_register1.php" class="nav_link sublink">Add Admin</a>
              <a href="view_user.php" class="nav_link sublink">Manage Users</a>
              <a href="#" class="nav_link sublink"></a>
              
            </ul>
          </li>
          <!-- end -->
        </ul>

       

        <!-- Sidebar Open / Close -->
        <div class="bottom_content">
          <div class="bottom expand_sidebar">
            <span> Expand</span>
            <i class='bx bx-log-in' ></i>
          </div>
          <div class="bottom collapse_sidebar">
            <span> Collapse</span>
            <i class='bx bx-log-out'></i>
          </div>
        </div>
      </div>
    </nav>
    <!-- JavaScript -->
    <script >
        const body = document.querySelector("body");
        const darkLight = document.querySelector("#darkLight");
        const sidebar = document.querySelector(".sidebar");
        const submenuItems = document.querySelectorAll(".submenu_item");
        const sidebarOpen = document.querySelector("#sidebarOpen");
        const sidebarClose = document.querySelector(".collapse_sidebar");
        const sidebarExpand = document.querySelector(".expand_sidebar");
        sidebarOpen.addEventListener("click", () => sidebar.classList.toggle("close"));

        sidebarClose.addEventListener("click", () => {
          sidebar.classList.add("close", "hoverable");
        });
        sidebarExpand.addEventListener("click", () => {
          sidebar.classList.remove("close", "hoverable");
        });

        sidebar.addEventListener("mouseenter", () => {
          if (sidebar.classList.contains("hoverable")) {
            sidebar.classList.remove("close");
          }
        });
        sidebar.addEventListener("mouseleave", () => {
          if (sidebar.classList.contains("hoverable")) {
            sidebar.classList.add("close");
          }
        });

        darkLight.addEventListener("click", () => {
          body.classList.toggle("dark");
          if (body.classList.contains("dark")) {
            document.setI
            darkLight.classList.replace("bx-sun", "bx-moon");
          } else {
            darkLight.classList.replace("bx-moon", "bx-sun");
          }
        });

        submenuItems.forEach((item, index) => {
          item.addEventListener("click", () => {
            item.classList.toggle("show_submenu");
            submenuItems.forEach((item2, index2) => {
              if (index !== index2) {
                item2.classList.remove("show_submenu");
              }
            });
          });
        });

        if (window.innerWidth < 768) {
          sidebar.classList.add("close");
        } else {
          sidebar.classList.remove("close");
        }
            </script>
  </body>
</html>