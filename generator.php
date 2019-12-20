<?php
	
	const NAME					= 'John';
	const CARD 					= '6668';
	const AMOUNT 				= 4000;
	const MIN_RECEIPT_AMOUNT 	= 10;
	const MAX_RECEIPT_AMOUNT 	= 100;

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	class Drivers
	{
	    private $gender;
		private $name;
		private $photo;

		private $availableNames;

		public function setRandomGender() {
	    	$this->setGender('male');
	    	if (rand(0, 3) == 0) {
	    		$this->setGender('female');
	    	}
	    }

	    private function setGender($gender) {
	    	$this->gender = $gender;
	    }

	    public function getGender() {
	    	return $this->gender;
	    }

	    public function setName() {
	    	$gender = $this->gender;
	    	$nameList = json_decode(file_get_contents("names/$gender.json"));
			$this->name = $nameList[rand(0, count($nameList)-1)];
	    }

		public function getName() {
			return $this->name;
		}

		public function setPhoto() {
			$gender = $this->gender;
			$path = "images/$gender/";
			$files = scandir($path);
			$this->photo = $path . $files[rand(2,count($files)-1)];
		}

		public function getPhoto() {
			return $this->photo;
		}

		public function getPhotoPath($file='') {
			$gender = $this->gender;
			return "images/$gender/$file";
		}

		private function setAvailableNames() {
			$gender = $this->getGender();
			$this->availableNames = json_decode(file_get_contents("names/$gender.json"));
		}

		private function getPhotoDirectory() {
			$path = $this->getPhotoPath();
			if ($handle = opendir($path)) {
			    while (false !== ($entry = readdir($handle))) {
			        if ($entry != "." && $entry != ".." && $entry !== ".DS_Store") {
			        	$photoDirectory[] = $entry;
			        }
			    }
			    closedir($handle);
			}
			return $photoDirectory;
		}

		private function removeExisting() {
			$path = $this->getPhotoPath();
			$availableNames = $this->availableNames;
			foreach ($this->getPhotoDirectory() as $file) {
	        	$fileName = explode('.', $file)[0];
	        	$index = array_search($fileName, $availableNames);
	        	unset($availableNames[$index]);
			}
        	$this->availableNames = array_values($availableNames);
		}

		public function namePhotos() {
			$genders = ['male', 'female'];
			foreach ($genders as $gender) {
				$this->setGender($gender);
				$this->setAvailableNames();
				$this->removeExisting();
				$path = $this->getPhotoPath();
				foreach ($this->getPhotoDirectory() as $entry) {
					$randomName = $this->availableNames[rand(0, count($this->availableNames)-1)];
		        	$extension = pathinfo($path.$entry, PATHINFO_EXTENSION);
		            rename($path.$entry, $path.$randomName.'.png');
		            unset($this->availableNames[array_search($randomName, $this->availableNames)]);
		            $this->availableNames = array_values($this->availableNames);
				}
			}
		}

		private function formatName($name) {
			return explode('.', $name)[0];
		}

		private function getDrivers($gender) {
			$this->setGender($gender);
			foreach ($this->getPhotoDirectory() as $file) {

				$sub_total = [
					'sub_total' => $this->getRandomTotal(MAX_RECEIPT_AMOUNT),
					'black_car' => $this->getRandomTotal(2),
					'tnc' =>  $this->getRandomTotal(1),
					'fee' =>  $this->getRandomTotal(2),
				];

				$total = $this->getFinalTotal($sub_total);

				$data = [
					'date' => $this->getRandomDate(),
					'sub_total' => $sub_total,
					'points' => (int)$total*2,
					'total' => $total,
					'driver' => [
						'name' => $this->formatName($file),
						'image' => $this->getPhotoPath($file)
					],
					'rating' => '4.' . number_format(rand(0,99))
				];

				$drivers[] = $data;

			}
			return $drivers;
		}

		public function createDriverList() {
			$this->namePhotos();
			$driverList = array_merge($this->getDrivers('male'), $this->getDrivers('female'));
			sort($driverList);
			return $driverList;
		}

		private function getRandomDate() {
			$lastYear = date("Y", strtotime("-1 year"));
			$start = strtotime("01 January $lastYear");
			$end = time();
			$timestamp = mt_rand($start, $end);
			return date("D, M j, y", $timestamp);
		}

		private function getRandomTotal($max) {
			$total = rand(0, $max).'.'.rand(0,99);
			return number_format($total, 2);
		}

		private function getFinalTotal($sub_total) {
			$total = 0;
			foreach ($sub_total as $price) {
				$total += $price;
			}
			return number_format($total, 2);
		}

	}

	function prePrint($array) {
		echo "<pre>";
		print_R($array);
		echo "</pre>";
	}

?>

<div style="width:700px">
	<?php $drivers = new Drivers(); ?>
	<?php $count = 0; ?>
	<?php foreach ($drivers->createDriverList() as $data): ?>
		
		<?php if ($count <= AMOUNT): ?>
			<?php include('receipt.php'); ?>
			<?php $count += $data['total']; ?>
		<?php endif; ?>

	<?php endforeach; ?>
	
	<?php if ($count < AMOUNT): ?>
		<script>
			alert('Was not able to generate enough receipts to meet AMOUNT, please add more images. Currently reached <?php echo $count; ?>');
		</script>
	<?php endif; ?>

	<?php prePrint($count); ?>
</div>
