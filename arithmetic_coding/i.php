<!DOCTYPE html>
<html>

<head>
	<title>Arithmetic Coding</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 0;
			padding: 0;
			background-color: #f4f4f4;
		}

		.container {
			width: 50%;
			margin: 50px auto;
			padding: 20px;
			background-color: #fff;
			border-radius: 10px;
			box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
		}

		h2 {
			text-align: center;
			color: #333;
		}

		form {
			text-align: center;
		}

		input[type="number"] {
			padding: 8px;
			margin: 5px;
			width: 200px;
			border-radius: 5px;
			border: 1px solid #ccc;
		}

		button[type="submit"] {
			padding: 10px 20px;
			margin-top: 10px;
			background-color: #4CAF50;
			color: white;
			border: none;
			border-radius: 5px;
			cursor: pointer;
		}

		button[type="submit"]:hover {
			background-color: #45a049;
		}

		.result {
			margin-top: 20px;
			text-align: center;
		}

		.table-container {
			display: table;
			width: 100%;
			border-collapse: collapse;
		}

		.table-row {
			display: table-row;
		}

		.header {
			font-weight: bold;
		}

		.table-cell {
			display: table-cell;
			padding: 8px;
			border: 1px solid #ddd;
		}

		p {
			max-width: 100%;
			word-wrap: break-word;
		}
	</style>
</head>

<?php

class Arithmetic
{

	private  $first_qtr;
	private  $half;
	private  $third_qtr;

	public function __construct()
	{
		$this->first_qtr = (int)(65535 / 4 + 1);
		$this->half = (int)(2 * $this->first_qtr);
		$this->third_qtr = (int)(3 * $this->first_qtr);
		$this->debagFlag = false;
	}

	/* Основные объекты для работы с текстом */
	private  $text;
	private  $abc;
	private  $encode;
	private  $decode;
	private $debagFlag;
	private $freq;

	public function init($string)
	{
		$this->set_string($string);
		$this->get_abc();
		$this->get_frequency();
		$this->encode_text();
		$this->decode_text();
	}

	public function getEncode()
	{
		return $this->encode;
	}

	public function getDecode()
	{
		return $this->decode;
	}

	private function set_string($input)
	{
		$this->text = $input . "\r";
	}

	public function show_table()
	{
		echo '<div class="table-container">';
		echo '<div class="table-row header">';
		echo '<div class="table-cell">Элемент алфовита</div>';
		echo '<div class="table-cell">Частота</div>';
		echo '</div>';

		for ($i = 1; $i < strlen($this->abc) - 1; $i++) {
			echo '<div class="table-row">';
			echo '<div class="table-cell">' . $this->abc[$i]  . '</div>';
			echo '<div class="table-cell">' . $this->freq[$i] - $this->freq[$i - 1] . '</div>';
			echo '</div>';
		}

		echo '</div>';
	}

	/* Создать алфавит */
	private function get_abc()
	{
		$exit_flag = false;
		$this->abc .= '-';
		for ($i = 0; $i < strlen($this->text); $i++) {
			$exit_flag = false;

			for ($j = 0; $j < strlen($this->abc) && !$exit_flag; $j++) {
				if ($this->abc[$j] == $this->text[$i]) {
					$exit_flag = true;
				}
			}

			if (!$exit_flag) {
				$this->abc .= $this->text[$i];
			}
		}
	}


	// на заметку, функия не находит кол-во вхождений элементов алфавита в тексте,
	// а находит накопительную частоту
	private function get_frequency()
	{
		$this->freq = array_fill(0, strlen($this->abc), 0);

		for ($j = 0; $j < strlen($this->abc); $j++) {
			for ($i = 0; $i < strlen($this->text); $i++) {
				if ($this->abc[$j] == $this->text[$i]) {
					$this->freq[$j]++;
				}
			}

			if ($j > 0) {
				$this->freq[$j] += $this->freq[$j - 1];
			}
		}
	}

	private function get_next_symbol($i)
	{
		$exit = false;
		for (; $i < strlen($this->text); $i++) {
			for ($j = 0; $j < strlen($this->abc); $j++) {
				if ($this->text[$i] == $this->abc[$j]) {
					return $j;
				}
			}
		}
	}


	/* Записать биты */
	private function write_bits($bit, $bits_to_foll)
	{
		$temp = (string) $bit;
		if ($bit == 0) {
			$bit = 1;
		} else {
			$bit = 0;
		}

		while ($bits_to_foll > 0) {
			$temp .= (string)($bit);
			$bits_to_foll -= 1;
		}

		return $temp;
	}

	/* Метод кодирования текста */
	private function encode_text()
	{
		$mass_size = strlen($this->text);
		$_low = array_fill(0, $mass_size, 0);
		$_high = array_fill(0, $mass_size, 0);
		$_low[0] = 0;
		$_high[0] = 65535;
		$current = 1;
		$i = 0;
		$range = 0;

		$del = $this->freq[strlen($this->abc) - 1];
		$bits_to_foll = 0;
		$code = "";

		while ($i < $mass_size) {
			$current = $this->get_next_symbol($i);
			$i++;

			$range = $_high[$i - 1] - $_low[$i - 1] + 1;
			$_low[$i] = (int)($_low[$i - 1] + ($range * $this->freq[$current - 1]) / $del);
			$_high[$i] = (int)($_low[$i - 1] + ($range * $this->freq[$current]) / $del - 1);

			if ($this->debagFlag) {
				echo "[" . $_low[$i] . "; " . $_high[$i] . ")<br>";
			}

			for (;;) {
				if ($_high[$i] < $this->half) {
					$code .= $this->write_bits(0, $bits_to_foll);
					$bits_to_foll = 0;
				} elseif ($_low[$i] >= $this->half) {
					$code .= $this->write_bits(1, $bits_to_foll);
					$bits_to_foll = 0;
					$_low[$i] -= $this->half;
					$_high[$i] -= $this->half;
				} elseif ($_low[$i] >= $this->first_qtr && $_high[$i] < $this->third_qtr) {
					$bits_to_foll++;
					$_low[$i] -= $this->first_qtr;
					$_high[$i] -= $this->first_qtr;
				} else {
					break;
				}

				$_low[$i] = 2 * $_low[$i];
				$_high[$i] = 2 * $_high[$i] + 1;
			}
		}

		$this->encode = $code;
	}




	/* Перевод 16 бит строки в int */
	private function to_int($pos)
	{
		$n = 0;
		for ($i = $pos; $i < 16 + $pos; $i++) {
			$n <<= 1;
			$n |= (int)($this->encode[$i]);
		}
		return $n;
	}



	/* Метод добавления бита к int числу */
	private function add_bit($value, $count_taken, &$flag)
	{
		// Создаем строку битов
		$a = decbin($value);
		$a = str_pad($a, 16, '0', STR_PAD_LEFT); // Дополняем нулями слева, чтобы получить 16 бит

		if ($flag) {
			$a[15] = '0';
		} elseif ($count_taken >= strlen($this->encode)) {
			$a[15] = '1';
			$flag = true;
		} elseif ($this->encode[$count_taken] == '1') {
			$a[15] = '1';
		} elseif ($this->encode[$count_taken] == '0') {
			$a[15] = '0';
		}

		$value = (int)bindec($a);
		return $value;
	}


	/* Метод раскодирования текста */
	private function decode_text()
	{
		$decode_text = "";
		$mass_size = strlen($this->text);
		$_low = array_fill(0, $mass_size, 0);
		$_high = array_fill(0, $mass_size, 0);
		$_low[0] = 0;
		$_high[0] = 65535;
		$range = 0;
		$cum = 0;
		$del = $this->freq[strlen($this->abc) - 1];

		$value = $this->to_int(0);

		if ($this->debagFlag) {
			echo "<br> Value = " . $value . "<br>";
		}

		$count_taken = 16;

		$flag = false;


		for ($i = 1;; $i++) {
			$range = ($_high[$i - 1] - $_low[$i - 1]) + 1;
			$cum = (int)(((($value - $_low[$i - 1]) + 1) * $del - 1) / $range);

			$symbol = 1;
			for (; $this->freq[$symbol] <= $cum; $symbol++);



			$_low[$i] = (int)($_low[$i - 1] + ($range * $this->freq[$symbol - 1]) / $del);
			$_high[$i] = (int)($_low[$i - 1] + ($range * $this->freq[$symbol]) / $del - 1);

			$decode_text .= $this->abc[$symbol];

			if ($this->debagFlag) {
				echo "Symbol is: " . $this->abc[$symbol] . "<br>";
				echo "Value is: " . $value . "<br>";
				echo "Current string is: " . $decode_text . "<br><br>";
			}


			if ($this->abc[$symbol] == "\r") {
				$this->decode = $decode_text;
				return;
			}

			while (true) {
				if ($_high[$i] >= $this->half) {
					if ($_low[$i] >= $this->half) {
						$value -= $this->half;
						$_low[$i] -= $this->half;
						$_high[$i] -= $this->half;
					} elseif ($_low[$i] >= $this->first_qtr && $_high[$i] < $this->third_qtr) {
						$value -= $this->first_qtr;
						$_low[$i] -= $this->first_qtr;
						$_high[$i] -= $this->first_qtr;
					} else {
						break;
					}
				}

				$_low[$i] = 2 * $_low[$i];
				$_high[$i] = 2 * $_high[$i] + 1;
				$value = (int)$this->add_bit(2 * $value, $count_taken, $flag);
				$count_taken++;
			}
		}
	}
}

?>


<body>
	<div class="container">
		<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
			<input type="text" name="string" placeholder="Введите строку"><br>
			<button type="submit" name="submit">Вычислить</button><br>

			<?php
			if (isset($_POST['submit'])) {
				$string = $_POST['string'];
				$arithmetic = new Arithmetic();
				$arithmetic->init($string);

				echo "<h2>Результаты кодирования и декодирования</h2>";
				echo "<h3>Закодированный текст</h3>";
				echo "<p>" . $arithmetic->getEncode() . "</p>";

				echo "<h3>Таблица частот и символов</h3>";
				$arithmetic->show_table();

				echo "<h3>Раскодированный текст</h3>";
				echo "<p>" . $arithmetic->getDecode() . "</p>";
			}
			?>
		</form>


	</div>


</body>

</html>