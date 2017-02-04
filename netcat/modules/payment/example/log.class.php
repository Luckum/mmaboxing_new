<?php 

// пример подключения логгера

class Example_Logger {
	
	public function __construct () {
		$nc_core = nc_Core::get_object();
		$nc_core->event->bind($this, array(nc_payment_system::EVENT_ON_PAY_SUCCESS  => 'on_pay_success'));
	}
	
	public function pay_success(nc_payment_system $payment_system) {
		// здесь реализуем действия записи в лог
		// доступен объект платежной системы $payment_system со всеми параметрами
	}
	
}

