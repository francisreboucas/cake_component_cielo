# Componente E-Commerce Cielo para CAKEPHP

*TODO:
* Ainda não está documentado.
* Ainda não está testado.

# Uso

	<?php class CarrinhoController extends AppController {

		public $name = 'Carrinho';
		public $uses = array();
		public $components = array('Cielo');
	
		public function checkout(){
			$this->Cielo->teste = true;
			$this->Cielo->url_retorno = "http://localhost/cielo/carrinho/retorno";
			$this->Cielo->parcelas = 2;
			$this->Cielo->pedido = 1;
			$this->Cielo->valor = 400.99;
	
			$arrReturn = $this->Cielo->enviarPedido();
	
	
			if( !empty( $arrReturn['Transacao']['url-autenticacao'] ) ){
					$this->Session->write( 'Tid', $arrReturn['Transacao']['tid'] );     
				$this->redirect($arrReturn['Transacao']['url-autenticacao']);   
			}
	   }
	   
	   public function retorno(){
			$this->Cielo->teste = true;
			$tid =  $this->Session->read('Tid');
			debug($this->Cielo->consultarPedido($tid));
			die();
	   }
	 }
	?>

