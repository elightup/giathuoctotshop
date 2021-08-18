<?php

namespace ELUSHOP\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class export {

	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_action( 'init', array( $this, 'generate_xlsx' ) );
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		add_users_page( 'Export user', 'Export user', 'list_users', 'export-users', array( $this, 'show' ) );
	}


	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function generate_xlsx() {

		if (  ! isset( $_POST['_wpnonce-export-users'] ) || ! isset( $_POST['user_fields'] ) ) {
			return;
		}

		check_admin_referer( 'export-users', '_wpnonce-export-users' );

		$file = 'export-user-' . date( 'd-m-Y' ) . ".xlsx";

		/**
		 * Generate .xlsx file using PHP_XLSXWriter class
		 * @link https://github.com/mk-j/PHP_XLSXWriter
		 */
		

		// Create new PHPExcel object
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		// Add some data
		$sheet->setCellValue( 'A1', 'STT' )
				->setCellValue( 'B1', 'Tên truy cập' )
				->setCellValue( 'C1', 'Email' )
				->setCellValue( 'D1', 'Tên hiển thị' )
				->setCellValue( 'E1', 'Số điện thoại' )
				->setCellValue( 'F1', 'Họ tên User' )
				->setCellValue( 'G1', 'Ngày sinh' )
				->setCellValue( 'H1', 'Hình thức kinh doanh' )
				->setCellValue( 'I1', 'Tỉnh' )
				->setCellValue( 'J1', 'Địa chỉ' )
				->setCellValue( 'K1', 'Cơ sở kinh doanh' )
				->setCellValue( 'L1', 'Ngày đăng ký' );


		$args = array(
			'meta_key'     => 'user_province',
			'meta_value'   => $_POST['address-users'],
			'meta_compare' => 'LIKE',
		);

		$users = get_users( $args );
		// $users = get_users( [] );

		$row = 1;
		foreach ( $users as $user ) {

			foreach ( $_POST['user_fields'] as $fields ) {
				if ( 'user_display_name' == $fields ) {
					$last_name  = get_user_meta( $user->ID, 'last_name', true );
					$first_name = get_user_meta( $user->ID, 'first_name', true );
					$user_display_name = $last_name . $first_name;
				}

				if ( 'user_sdt' == $fields ) {
					$user_sdt = get_user_meta( $user->ID, 'user_sdt', true );
				}
				if ( 'user_name' == $fields ) {
					$user_name = get_user_meta( $user->ID, 'user_name', true );
				}
				if ( 'user_date_birth' == $fields ) {
					$user_date_birth = get_user_meta( $user->ID, 'user_date_birth', true );
				}
				if ( 'user_hinhthuc_kd' == $fields ) {
					$user_hinhthuc_kd = get_user_meta( $user->ID, 'user_hinhthuc_kd', true );
				}
				if ( 'user_province' == $fields ) {
					$user_province = get_user_meta( $user->ID, 'user_province', true );
				}
				if ( 'user_address' == $fields ) {
					$user_address = get_user_meta( $user->ID, 'user_address', true );
				}
				if ( 'user_ten_csdk' == $fields ) {
					$user_ten_csdk = get_user_meta( $user->ID, 'user_ten_csdk', true );
				}
				if ( 'user_registered' == $fields ) {
					$user_registered = date( 'd.m.Y', strtotime( $user->user_registered ) );
				}

			}

			$row ++;
			// Add some data
			$sheet->setCellValue( 'A' . $row, $row - 1 )
					->setCellValue( 'B' . $row, $user->user_login )
					->setCellValue( 'C' . $row, $user->user_email )
					->setCellValue( 'D' . $row, $user_display_name )
					->setCellValue( 'E' . $row, $user_sdt )
					->setCellValue( 'F' . $row, $user_name )
					->setCellValue( 'G' . $row, $user_date_birth )
					->setCellValue( 'H' . $row, $user_hinhthuc_kd )
					->setCellValue( 'I' . $row, $user_province )
					->setCellValue( 'J' . $row, $user_address )
					->setCellValue( 'K' . $row, $user_ten_csdk )
					->setCellValue( 'L' . $row, $user_registered );

		}


		$writer = new Xlsx( $spreadsheet );
		ob_end_clean();

		// Redirect output to a client’s web browser (Excel2007)
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="' . $file . '"' );
		header( 'Cache-Control: max-age=0' );
		// If you're serving to IE 9, then the following may be needed
		header( 'Cache-Control: max-age=1' );

		// If you're serving to IE over SSL, then the following may be needed
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' ); // Date in the past
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); // always modified
		header( 'Cache-Control: cache, must-revalidate' ); // HTTP/1.1
		header( 'Pragma: public' ); // HTTP/1.0

		$writer->save( 'php://output' );
		exit;

	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public function show() {
		?>
		<div class="wrap">
		<h2>Xuất danh sách khách hàng ra file excel</h2>

		<form method="post" action="" enctype="multipart/form-data" novalidate>
			<?php wp_nonce_field( 'export-users', '_wpnonce-export-users' ); ?>
			<p id="action-address">
				<label>Chọn tỉnh:</label>
				<select name="address-users" id="number-users">
					<option value="803">An Giang</option>
					<option value="743">Bắc Giang</option>
					<option value="744">Bắc Kạn</option>
					<option value="745">Bạc Liêu</option>
					<option value="746">Bắc Ninh</option>
					<option value="747">Bà Rịa - Vũng Tàu</option>
					<option value="748">Bến Tre</option>
					<option value="741">Bình Định</option>
					<option value="742">Bình Dương</option>
					<option value="780">Bình Phước</option>
					<option value="749">Bình Thuận</option>
					<option value="751">Cà Mau</option>
					<option value="781">Cần Thơ</option>
					<option value="750">Cao Bằng</option>
					<option value="753">Đắk Lắk</option>
					<option value="755">Đắk Nông</option>
					<option value="754">Đà Nẵng</option>
					<option value="752">Điện Biên</option>
					<option value="756">Đồng Nai</option>
					<option value="757">Đồng Tháp</option>
					<option value="758">Gia Lai</option>
					<option value="762">Hà Giang</option>
					<option value="760">Hải Dương</option>
					<option value="764">Hải Phòng</option>
					<option value="763">Hà Nam</option>
					<option value="784">Hà Nội</option>
					<option value="765">Hà Tĩnh</option>
					<option value="761">Hậu Giang</option>
					<option value="759">Hoà Bình</option>
					<option value="782">Huế</option>
					<option value="766">Hưng Yên</option>
					<option value="768">Khánh Hoà</option>
					<option value="767">Kiên Giang</option>
					<option value="769">Kon Tum</option>
					<option value="772">Lai Châu</option>
					<option value="773">Lâm Đồng</option>
					<option value="774">Lạng Sơn</option>
					<option value="771">Lào Cai</option>
					<option value="770">Long An</option>
					<option value="777">Nam Định</option>
					<option value="775">Nghệ An</option>
					<option value="776">Ninh Bình</option>
					<option value="778">Ninh Thuận</option>
					<option value="785">Phú Thọ</option>
					<option value="786">Phú Yên</option>
					<option value="787">Quảng Bình</option>
					<option value="790">Quảng Nam</option>
					<option value="789">Quảng Ngãi</option>
					<option value="788">Quảng Ninh</option>
					<option value="791">Quảng Trị</option>
					<option value="793">Sóc Trăng</option>
					<option value="792">Sơn La</option>
					<option value="797">Tây Ninh</option>
					<option value="794">Thái Bình</option>
					<option value="779">Thái Nguyên</option>
					<option value="796">Thanh Hoá</option>
					<option value="795">Tiền Giang</option>
					<option value="783">TP Hồ Chí Minh</option>
					<option value="799">Trà Vinh</option>
					<option value="798">Tuyên Quang</option>
					<option value="800">Vĩnh Long</option>
					<option value="801">Vĩnh Phúc</option>
					<option value="802">Yên Bái</option>
				</select>
			</p>
			<div class="option">
				<input type="checkbox" name="user_fields[]" value="user_login" checked>Tên truy cập<br>
				<input type="checkbox" name="user_fields[]" value="user_email" checked>Email<br>
				<input type="checkbox" name="user_fields[]" value="user_display_name" checked>Tên hiển thị<br>
				<input type="checkbox" name="user_fields[]" value="user_sdt" checked>Số điện thoại<br>
				<input type="checkbox" name="user_fields[]" value="user_name" checked>Họ tên User<br>
				<input type="checkbox" name="user_fields[]" value="user_date_birth" checked>Ngày sinh<br>
				<input type="checkbox" name="user_fields[]" value="user_hinhthuc_kd" checked>Hình thức kinh doanh<br>
				<input type="checkbox" name="user_fields[]" value="user_province" checked>Tỉnh<br>
				<input type="checkbox" name="user_fields[]" value="user_address" checked>Địa chỉ<br>
				<input type="checkbox" name="user_fields[]" value="user_ten_csdk" checked>Tên cơ sở kd<br>
				<input type="checkbox" name="user_fields[]" value="user_registered" checked>Ngày đăng ký<br>
			</div>
			<p class="submit">
				<input type="submit" class="button-primary" value="Export"/>
			</p>
		</form>
		<?php
	}
}
