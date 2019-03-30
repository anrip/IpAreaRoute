<?php

chdir(__DIR__);

date_default_timezone_set('Asia/Shanghai');

is_dir('./data') || mkdir('./data');

confirm("��������������ԭ����,��ȷ��") || exit;

////////////////////////////////////////////////////

//��ʼʱ��
$time1 = time();

echo("��ȡAPNIC����\n");
$apnic = get_apnic_data();

echo("��ȡ�й�IPv4��ַ\n");
get_country_ipv4('china', 'cn', $apnic);

echo("��ȡ����IPv4��ַ\n");
get_country_ipv4('oversea', '(?!cn)\w{2}', $apnic);

//�����ʱ
$time2 = time() - $time1;
echo("\n\n[+] ת����������ʱ��ԼΪ{$time2}��.");

////////////////////////////////////////////////////

/**
 * ѯ�ʲ���
 */
function confirm($text) {
    echo("{$text}[yes/no]: ");
    $stat = trim(fgets(STDIN));
    return $stat == 'y' || $stat == 'yes';
}

/**
 * ��ȡAPNIC����
 */
function get_apnic_data() {
	$file = './data/apnic.txt';
	if(is_file($file) && filectime($file) > strtotime('-1 day')) {
		return file_get_contents($file);
	}
	$site = 'http://ftp.apnic.net/apnic/stats/apnic/delegated-apnic-latest';
	file_put_contents($file, $data = file_get_contents($site));
	return $data;
}

/**
 * ��ȡָ������IPv4��ַ
 */
function get_country_ipv4($name, $expr, &$data) {
	$expr = "/apnic\|{$expr}\|ipv4\|([0-9\.]+\|[0-9]+)\|[0-9]+\|a.*/i";
	preg_match_all($expr, $data, $match);
	$rest = array(array(), array());
	foreach($match[1] as $val) {
		list($net, $ips) = explode('|', $val);
		$rest[0][] = $net.'/'.(32 - log($ips, 2));
		$rest[1][] = $net.'/'.(long2ip(ip2long('255.255.255.255') << log($ips, 2)));
	}
	file_put_contents("./data/apnic_{$name}_0_v4.txt", implode("\n", $rest[0]));
	file_put_contents("./data/apnic_{$name}_1_v4.txt", implode("\n", $rest[1]));
	//����Linux·�ɱ�
	$route = 'route add -net $1 netmask $2 gw ${gwip}';
	$route =  preg_replace('@([^/]+)/([^/]+)@', $route, $rest[1]);
	file_put_contents("./data/apnic_{$name}_v4_linux_route.add", implode("\n", $route));
}

///////////////////////////////////////////////////////////////////////////////////////////////

/**
 * IPv4��ַת����
 * $ip = new ipv4('192.168.2.1', 24);
 */

class ipv4 {
	//������
	private $address;
	private $netbits;
	//���캯��
	public function __construct($address, $netbits, $type = '') {
		$this->address = $address;
		$this->netbits = $netbits;
		if($type == 'netips') {
			$this->set_netbits_by_netips();
		}
	}
	//��ȡIP��ַ
	public function address() {
		return ($this->address);
	}
	//��ȡ����λ��
	public function netbits() {
		return ($this->netbits);
	}
	//��ȡ��������
	public function netmask() {
		return (long2ip(ip2long('255.255.255.255') << (32 - $this->netbits)));
	}
	//��ȡ������
	public function inverse() {
		return (long2ip( ~ (ip2long('255.255.255.255') << (32 - $this->netbits))));
	}
	//��ȡ������ַ
	public function network() {
		return (long2ip((ip2long($this->address)) & (ip2long($this->netmask()))));
	}
	//��ȡ�㲥��ַ
	public function broadcast() {
		return (long2ip(ip2long($this->network()) | ( ~ (ip2long($this->netmask())))));
	}
	//���ݿ���IP��ȡ����λ��
	private function set_netbits_by_netips() {
		$this->netbits = 32 - log($this->netbits, 2);
	}
}
