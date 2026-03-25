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
        <!-- Danh sách sản phẩm -->
        <section class="jumbotron text-center">
            <div class="container">
                <h1 class="jumbotron-heading">Guest List</h1>
                <p class="lead text-muted">Các sản phẩm với chất lượng, uy tín, cam kết từ nhà Sản xuất, phân phối và
                    bảo hành
                    chính hãng.</p>
            </div>
        </section>

        <!-- Giải thuật duyệt và render Danh sách sản phẩm theo dòng, cột của Bootstrap -->
        <div class="danhsachsanpham py-5 bg-light">
            <div class="container">
			<?php
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
			$sql = "SELECT * FROM MyGuests";
			$result = $conn->query($sql);

			$conn->close();
			
			$count = 0;
			while($row = $result->fetch_assoc()) {
				if($count == 0) {
					echo '<div class="row">';
				}
			?> 	
				<div class="col-md-4">
					<div class="card mb-4 shadow-sm">
						<a href="product-detail.html">
							<img class="bd-placeholder-img card-img-top" width="100%" height="350"
								src="<?= $row['avatar'] ?>">
						</a>
						<div class="card-body">
							<h5><?= $row['username'] ?></h5>
						</div>
					</div>
				</div>
			<?php
				$count++;
				if($count == 3) {
					echo '</div>';
					$count = 0;
				}
			};
				if($count != 0) {
					echo '</div>';
				}
			?>
                
			</div>
        </div>

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