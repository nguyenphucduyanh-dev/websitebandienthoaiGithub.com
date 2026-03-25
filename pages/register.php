<?php
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kh_tendangnhap'])) {
	$name = $_POST['kh_tendangnhap'];
	$pass = $_POST['kh_matkhau'];
	
	$target_file = "";
	if (isset($_FILES['kh_avatar']) && $_FILES['kh_avatar']['error'] == 0) {
        $target_dir = "../assets/img/avatar/";
        $file_name = $_FILES['kh_avatar']['name'];
        $target_file = $target_dir . $file_name;

        // Chỉ thực hiện nếu file CHƯA tồn tại
        if (!file_exists($target_file)) {
            if (move_uploaded_file($_FILES['kh_avatar']['tmp_name'], $target_file)) {
				echo "Upload thành công!";
			}
        }
        // Nếu file đã tồn tại, script sẽ kết thúc ở đây mà không in ra bất cứ thứ gì
    }
	
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "myDBs7";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
	  die("Connection failed: " . $conn->connect_error);
	}
	
	// prepare and bind
	$stmt = $conn->prepare("INSERT INTO MyGuests (username, password, avatar) VALUES (?, ?, ?)");
	$stmt->bind_param("sss", $name, $pass, $target_file);
	
	$stmt->execute();
	
	echo "New records created successfully";
	
	$stmt->close();
	$conn->close();
}
?>
<!DOCTYPE html>
<html lang="vi" class="h-100">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Nền tảng - Kiến thức cơ bản về WEB | Bảng tin</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../vendor/bootstrap/css/bootstrap.min.css" type="text/css">
    <!-- Font awesome -->
    <link rel="stylesheet" href="../vendor/font-awesome/css/font-awesome.min.css" type="text/css">

    <!-- Custom css - Các file css do chúng ta tự viết -->
    <link rel="stylesheet" href="../assets/css/app.css" type="text/css">
</head>

<body>
    <!-- header -->
    <?php include "header.php"; ?>
	<!-- end header -->

    <main role="main">
        <!-- Block content - Đục lỗ trên giao diện bố cục chung, đặt tên là `content` -->
        <form name="frmdangky" id="frmdangky" method="post" action=""  enctype="multipart/form-data">
            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card mx-4">
                            <div class="card-body p-4">
                                <h1>Đăng ký</h1>
                                <p class="text-muted">Tạo tài khoản</p>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-user"></i>
                                        </span>
                                    </div>
                                    <input class="form-control" type="text" placeholder="Tên tải khoản"
                                        name="kh_tendangnhap">
                                </div>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fa fa-user"></i>
                                        </span>
                                    </div>
                                    <input class="form-control" type="password" placeholder="Mật khẩu"
                                        name="kh_matkhau">
                                </div>
                                <div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text">
											<i class="fa fa-camera"></i>
										</span>
									</div>
									<div class="custom-file">
										<input type="file" class="custom-file-input" id="kh_avatar" name="kh_avatar" accept="image/*">
										<label class="custom-file-label" for="kh_avatar">Chọn ảnh đại diện (Avatar)...</label>
									</div>
								</div>  
								<script>
									document.querySelector('.custom-file-input').addEventListener('change', function (e) {
										var fileName = document.getElementById("kh_avatar").files[0].name;
										var nextSibling = e.target.nextElementSibling;
										nextSibling.innerText = fileName;
									});
								</script>								
                                <button class="btn btn-block btn-success" name="btnDangKy">Tạo tài khoản</button>
                            </div>
                            <div class="card-footer p-4">
                                <div class="row">
                                    <div class="col-12">
                                        <center>Nếu bạn đã có Tài khoản, xin mời Đăng nhập</center>
                                        <a class="btn btn-primary form-control"
                                            href="login.php">Đăng nhập</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <!-- End block content -->
    </main>

    <!-- footer -->
    <footer class="footer mt-auto py-3">
        <div class="container">
            <span>Bản quyền © bởi <a href="https://nentang.vn">Nền Tảng</a> - <script>document.write(new Date().getFullYear());</script>.</span>
            <span class="text-muted">Hành trang tới Tương lai</span>

            <p class="float-right">
                <a href="#">Về đầu trang</a>
            </p>
        </div>
    </footer>
    <!-- end footer -->

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/popperjs/popper.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Custom script - Các file js do mình tự viết -->
    <script src="../assets/js/app.js"></script>

</body>

</html>