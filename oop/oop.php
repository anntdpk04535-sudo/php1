<?php

class Laptop {
    public $maMay;
    private $mauSac;
    public $kichThuoc;
    public $ram;
    public $oCung;

    //hàm khởi tạo
public function __construct($maMay, $mauSac, $kichThuoc, $ram, $oCung) {
    $this->maMay = $maMay;
    $this->mauSac = $mauSac;
    $this->kichThuoc = $kichThuoc;
    $this->ram = $ram;
    $this->oCung = $oCung;
}

function set_mauSac($mau) {
    return $this->mauSac = $mau;
}
function get_mauSac() {
    return $this->mauSac;
}

// method
public function xuatthongtin() {
    echo $this->maMay . "<br>";
    echo $this->mauSac . "<br>";
    echo $this->kichThuoc . "<br>";
    echo $this->ram . "<br>";
    echo $this->oCung ;
    
}

//bung

}

$laptop_hieu = new Laptop("msi01", "Trắng", "15inch", 16, 256);

$laptop_hieu->xuatthongtin();
$laptop_hieu->set_mauSac("đỏ");
$laptop_hieu->xuatthongtin();
echo "<hr>";
echo $laptop_hieu->get_mauSac();

?>