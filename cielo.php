<?php
/**
 * Cielo CakePHP Component
 * Copyright (c) 2011 Luan Garcia
 * @link www.implementado.com
 * @author      Luan Garcia <luan.garcia@gmail.com>
 * @version     1.0
 * @modified 	Francis Rebou�as <francisreboucas@gmail.com>
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package	app
 * @subpackage app.controller.components

 */

class CieloComponent extends Object {

	 /**
    * Ambiente: Teste ou Produ��o
    * @var boolean 
    */
    public $teste       = false;    												
    	/**
    * ID de afilia��o junto a cielo. Ex.:1001734898
    * @var string
    */  
    public $afiliacao      = '1001734898';    
	
	/**
    * Chave de Produ��o. Ex.:e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832
    * @var string
    */  
    public $chave      = 'e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832';   
	/**
    * Id do pedido na loja 
    * @var int
    */														
    public $pedido      = 1;   
	
	/**
    * Bandeira: visa ou master
    * @var string
    */	  																
    public $bandeira    = 'visa';     
	
	/**
    * Parcelas liberadas junto a afilica��o da Cielo
    * @var int
    */	
    public $parcelas    = 1;   
	
	/**
    * Valor do Pedido no padr�o sem virgula. Ex.: 1000 ser� R$10.00
    * @var int
    */	  																
    public $valor       = 1;   
	
	/**
    * Capturar o pedido automaticamente ou posteriormente
    * @var string true ou false
    */  												
    public $capturar    = 'false';  
	
	/**
    * Indicador de autoriza��o autom�tica: 0 (n�o autorizar) 1 (autorizar somente se autenticada) 2 (autorizar autenticada e n�o-autenticada) 
	* 3 (autorizar sem passar por autentica��o - v�lido somente para cr�dito)
    * @var int
    */ 															
    public $autorizar   = 2; 
	
	/**
    * URL para teste
    * @var string
    */ 
    public $url_teste   = 'https://qasecommerce.cielo.com.br/servicos/ecommwsec.do';	
	
	/**
    * URL para producao
    * @var string
    */ 		
    public $url         = 'https://ecommerce.cbmp.com.br/servicos/ecommwsec.do';				
	/**
    * URL para retorno apos informar os dados do cart�o na no by page cielo
    * @var string
    */ 
    public $url_retorno = '';   
	
	/**
    * Moeda - 986 (Real)
    * @var int
    */ 
    public $moeda 		= 986; 		
	
	/**
    * Idioma do Sistema
    * @var string
    */ 															
    public $linguagem 	= 'PT';																		
       

	function __construct(){
		// Importa XML
		App::import("Xml");
	}
    
     /**
     * Metodo realiza a criacao do pedido junto a visa
     * Return mixed dados do pedido na visa junto com TID do pedido,url-autenticacao (tela finalizacao da compra)
     */
    function enviarPedido() {

        /**
         * Se as parcelas forem > 1 produto=Cr�dito � Vista sen�o produto=Parcelado loja
         */
        $produto = $this->parcelas > 1 ? 2 : 1;
        /**
         * Data hora do pedido 
         */
        $data = date("Y-m-d\TH:i:s");
        /**
         * Limpa o valor para a visa
         */
        $valor = preg_replace('/[^0-9]+/', "", number_format($this->valor, 2, ",", "."));
        
        $post = '<?xml version="1.0" encoding="ISO-8859-1" ?>
					<requisicao-transacao id="' . md5(date("YmdHisu")) . '" versao="1.1.0">
					   <dados-ec>
					      <numero>'.$this->afiliacao.'</numero>
					      <chave>'.$this->chave.'</chave>
					   </dados-ec>
					   <dados-pedido>
					      <numero>'.$this->pedido.'</numero>
					      <valor>'.$valor.'</valor>
					      <moeda>'.$this->moeda.'</moeda>
					      <data-hora>'.$data.'</data-hora>
					      <idioma>'.$this->linguagem.'</idioma>
					   </dados-pedido>
					   <forma-pagamento>
					      <bandeira>'.$this->bandeira.'</bandeira>
					      <produto>'.$produto.'</produto>
					      <parcelas>'.$this->parcelas.'</parcelas>
					   </forma-pagamento>
					
					   <url-retorno>'.$this->url_retorno.'</url-retorno>
					   <autorizar>'.$this->autorizar.'</autorizar>
					   <capturar>'.$this->capturar.'</capturar>
					</requisicao-transacao>';
        
       
        
        $retorno = Set::reverse(new Xml($this->file_post_contents($post)));
         
        #Log para debug futuro em produ��o, facilita o debug no cliente
        
        if(isset($retorno['Erro'])) {
            $log =  var_export($retorno, true);
            $this->log('ERRO - AO CRIAR TID\r\n'.$log.'\r\n', LOG_DEBUG);
        }else {
            $log =  var_export($retorno, true);
            $this->log('SUCESSO - AO CRIAR TID\r\n'.$log.'\r\n', LOG_DEBUG);
        }
        return $retorno;
    }

   
    /**
     * Metodo realiza a consulta do pedido na visa
     * @param String TID da Transa��o
     * @return mixed
     */
    function consultarPedido($tid) {
        $post = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <requisicao-consulta id='" . md5(date("YmdHisu")) . "' versao=\"1.0.0\" xmlns=\"http://ecommerce.cbmp.com.br\">
                        <tid>{$tid}</tid>
                        <dados-ec>
                            <numero>{$this->afiliacao}</numero>
                            <chave>{$this->chave}</chave>
                        </dados-ec>
                    </requisicao-consulta>";

        $retorno_visa = Set::reverse(new Xml($this->file_post_contents($post)));
        
        return $retorno_visa;
    }
	
	 /**
     * Metodo realiza a captura do pedido na visa
     * @param String TID da Transa��o
     * @param int Valor da Transa��o
     * @param String Anexo
     * @return mixed
     */
	public function capturarPedido($tid, $valor, $anexo = null){
		
		if($anexo != null && $anexo != ""){
			$str_anexo = "<anexo>{$anexo}</anexo>";
		}else{
			$str_anexo = "";
		}
		
		$post = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <requisicao-captura id='" . md5(date("YmdHisu")) . "' versao=\"1.0.0\" xmlns=\"http://ecommerce.cbmp.com.br\">
                        <tid>{$tid}</tid>
                        <dados-ec>
                            <numero>{$this->afiliacao}</numero>
                            <chave>{$this->chave}</chave>
                        </dados-ec>
						<valor>{$valor}</valor>
						".$str_anexo."	
                    </requisicao-captura>";

        $retorno_visa = Set::reverse(new Xml($this->file_post_contents($post)));
        
        return $retorno_visa;
		
	}
	public function solicitarAutorizacao($tid){
		$post = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <requisicao-autorizacao-tid id='" . md5(date("YmdHisu")) . "' versao=\"1.0.0\" xmlns=\"http://ecommerce.cbmp.com.br\">
                        <tid>{$tid}</tid>
                        <dados-ec>
                            <numero>{$this->afiliacao}</numero>
                            <chave>{$this->chave}</chave>
                        </dados-ec>
                    </requisicao-autorizacao-tid>";

        $retorno_visa = Set::reverse(new Xml($this->file_post_contents($post)));
        
        return $retorno_visa;
	}
	/**
	 * Metodo realiza a solicita��o de cancelamento do pedido
	 * @param String TID da Transa��o
	 * @return mixed
	 */
	public function solicitarCancelamento($tid){
		$post = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <requisicao-cancelamento id='" . md5(date("YmdHisu")) . "' versao=\"1.0.0\" xmlns=\"http://ecommerce.cbmp.com.br\">
                        <tid>{$tid}</tid>
                        <dados-ec>
                            <numero>{$this->afiliacao}</numero>
                            <chave>{$this->chave}</chave>
                        </dados-ec>
                    </requisicao-cancelamento>";

        $retorno_visa = Set::reverse(new Xml($this->file_post_contents($post)));
        
        return $retorno_visa;
	}

    /**
     * Metodo realiza o post do xml local para a visa
     * @param String Xml com os dados do pedido
     * @return mixed
     */
    function file_post_contents($msg) {
        $postdata = http_build_query(array('mensagem' => $msg));

        $opts = array('http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);

        if ($this->teste === true) {
            $url = $this->url_teste;
        }else{
            $url = $this->url;
        }
        return file_get_contents($url, false, $context);
    }
    
    
    /**
     * Metodo retorna o status do pedido
     * @param String status com os dados do pedido
     * @return status cielo
     */
	function getStatus($status){

		switch($status){
			case "0": $status = "Criada";
					break;
			case "1": $status = "Em andamento";
					break;
			case "2": $status = "Autenticada";
					break;
			case "3": $status = "N�o autenticada";
					break;
			case "4": $status = "Autorizada";
					break;
			case "5": $status = "N�o autorizada";
					break;
			case "6": $status = "Capturada";
					break;
			case "8": $status = "N�o capturada";
					break;
			case "9": $status = "Cancelada";
					break;
			case "10": $status = "Em autentica��o";
					break;
			default: $status = "n/a";
					break;
		}
		
		return $status;
	}
    
}
?>
