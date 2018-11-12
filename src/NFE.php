<?PHP
/*
 * (c) Samuel Aiala <samuca@samuca.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace samhk222\NFE;

class Parser {

  public $num_nf;
  public $serie;
  public $data_emissao;
  public $num_chave_nf;
  public $valor_nf;
  public $quant_vol;
  public $peso;
  public $cod_cliente;


  public $tbl = "nota_fiscal_cantu";

  # Variáveis auxiliares
  public $data_emissao_u;
  public $data_emissao_f;

  /**
   * Campo que indica se é uma nota de venda ou armazenagem. Caso o CFOP seja 5905 é uma nota de armazenagem
   * Porém esse campo está nos ítens de venda (produto) e não nos dados da nota em si, então tenho que iteirar
   * o $this->produtos na validação da nota fiscal, caso o tipo dela seja de venda
   * @var boolean
   */
  public $isArmazenagem = false;

  # Campos auxiliares da nota
  public $cliente              = array();
  public $produtos             = array();
  public $produtos_complemento = array();
  public $emitente             = array();


  public function __construct(){
  }

  public function formatCNPJ($cnpj){
    # 27146290000213 vira 27.146.290/0002-13
    if (strlen($cnpj)==14){
      return sprintf("%s.%s.%s/%s-%s",substr($cnpj, 0,2),substr($cnpj, 2,3),substr($cnpj, 5,3),substr($cnpj, 8,4),substr($cnpj, -2));
    } else {
      return $cnpj;
    }
  }

  public function HasError(){
    return count($this->__msg__) > 0 ? true: false;
  }

  public function CHECK_VALID_NF($xml, $which = 'NF_SAIDA'){

    unset($this->__msg__);

    libxml_use_internal_errors(true);

    $xml = simplexml_load_string( $xml );

    if ($xml === false) {
      $this->__msg__[] = "O arquivo informado não é um XML válido";
      $this->__msg__[] = print_r(libxml_get_errors(),true);
      $this->__msg__[] = print_r(libxml_get_last_error(), true);
      return false;
    } else {

      if ( count($xml->NFe->infNFe->det) == 0){
        $this->__msg__[] = "A quantidade de ítens dessa nota está zerada";
      } else {
        foreach ($xml->NFe->infNFe->det as $rowProd){

          $item = (string) $rowProd->attributes()->{'nItem'};
       
          $this->produtos[$item]['cProd']    = (string) $rowProd->prod->cProd;
          $this->produtos[$item]['cEAN']     = (string) $rowProd->prod->cEAN;
          $this->produtos[$item]['xProd']    = (string) $rowProd->prod->xProd;
          $this->produtos[$item]['NCM']      = (string) $rowProd->prod->NCM;
          $this->produtos[$item]['CEST']     = (string) $rowProd->prod->CEST;
          $this->produtos[$item]['CFOP']     = (string) $rowProd->prod->CFOP;
          $this->produtos[$item]['qCom']     = (float) $rowProd->prod->qCom;
          $this->produtos[$item]['vUnCom']   = (float) $rowProd->prod->vUnCom;
          $this->produtos[$item]['vUnCom_f'] = 'R$ ' .number_format( (float) $rowProd->prod->vUnCom, 2, ',', '.');
          $this->produtos[$item]['vProd']    = (string) $rowProd->prod->vProd;
          $this->produtos[$item]['vProd_f']  = 'R$ ' .number_format( (float) $rowProd->prod->vProd, 2, ',', '.');
          $this->produtos[$item]['uTrib']    = (string) $rowProd->prod->uTrib;
          $this->produtos[$item]['qTrib']    = (string) $rowProd->prod->qTrib;
          $this->produtos[$item]['vUnTrib']  = (string) $rowProd->prod->vUnTrib;
          $this->produtos[$item]['indTot']   = (string) $rowProd->prod->indTot;

          $this->produtos_complemento[(int) $rowProd->prod->cProd]['xProd']    = (string) $rowProd->prod->xProd;
          $this->produtos_complemento[(int) $rowProd->prod->cProd]['qCom']     = (float) $rowProd->prod->qCom;
          $this->produtos_complemento[(int) $rowProd->prod->cProd]['vUnCom_f'] = 'R$ ' .number_format( (float) $rowProd->prod->vUnCom, 2, ',', '.');
          $this->produtos_complemento[(int) $rowProd->prod->cProd]['vProd_f']  = 'R$ ' .number_format( (float) $rowProd->prod->vProd, 2, ',', '.');
              
        }        
      }

      $dt_emissao  = substr($xml->NFe->infNFe->ide->dhEmi, 0, 10);


      if (!$this->verifyDate('Y-m-d', $dt_emissao)){
        $this->__msg__[] = "A data de emissão ($dt_emissao) está inválida"; 
      }

      $num_nf = preg_replace('/\D/', '', $xml->NFe->infNFe->ide->nNF);
      $serie  = preg_replace('/\D/', '', $xml->NFe->infNFe->ide->serie);

      $this->num_nf          = $num_nf;
      $this->serie           = $serie;
      $this->data_emissao    = $dt_emissao;
      $this->data_emissao_f  = $this->InverteData($dt_emissao);
      $this->num_chave_nf    = (string)$xml->protNFe->infProt->chNFe;
      $this->valor_nf        = (float)$xml->NFe->infNFe->total->ICMSTot->vNF;
      $this->quant_vol       = (float)$xml->NFe->infNFe->transp->vol->qVol;
      $this->peso            = (float)$xml->NFe->infNFe->transp->vol->pesoB;
      $this->cod_cliente     = (float) $xml->NFe->infNFe->dest->CNPJ;

      #Emitente
      $this->emitente['CNPJ']                 = (string) $xml->NFe->infNFe->emit->CNPJ;
      $this->emitente['xNome']                = (string) $xml->NFe->infNFe->emit->xNome;
      $this->emitente['xFant']                = (string) $xml->NFe->infNFe->emit->xFant;
      $this->emitente['IE']                   = (string) $xml->NFe->infNFe->emit->IE;
      $this->emitente['CRT']                  = (string) $xml->NFe->infNFe->emit->CRT;
      $this->emitente['enderEmit']['xLgr']    = (string) $xml->NFe->infNFe->emit->enderEmit->xLgr;
      $this->emitente['enderEmit']['nro']     = (string) $xml->NFe->infNFe->emit->enderEmit->nro;
      $this->emitente['enderEmit']['xCpl']    = (string) $xml->NFe->infNFe->emit->enderEmit->xCpl;
      $this->emitente['enderEmit']['xBairro'] = (string) $xml->NFe->infNFe->emit->enderEmit->xBairro;
      $this->emitente['enderEmit']['cMun']    = (string) $xml->NFe->infNFe->emit->enderEmit->cMun;
      $this->emitente['enderEmit']['xMun']    = (string) $xml->NFe->infNFe->emit->enderEmit->xMun;
      $this->emitente['enderEmit']['UF']      = (string) $xml->NFe->infNFe->emit->enderEmit->UF;
      $this->emitente['enderEmit']['CEP']     = (string) $xml->NFe->infNFe->emit->enderEmit->CEP;
      $this->emitente['enderEmit']['cPais']   = (string) $xml->NFe->infNFe->emit->enderEmit->cPais;
      $this->emitente['enderEmit']['xPais']   = (string) $xml->NFe->infNFe->emit->enderEmit->xPais;
      $this->emitente['enderEmit']['fone']    = (string) $xml->NFe->infNFe->emit->enderEmit->fone;
          

      #Campos auxiliares da nota
      $this->cliente['CNPJ']                 = (string) $xml->NFe->infNFe->dest->CNPJ;
      $this->cliente['xNome']                = (string) $xml->NFe->infNFe->dest->xNome;
      $this->cliente['enderDest']['xLgr']    = (string) $xml->NFe->infNFe->dest->enderDest->xLgr;
      $this->cliente['enderDest']['nro']     = (string) $xml->NFe->infNFe->dest->enderDest->nro;
      $this->cliente['enderDest']['xBairro'] = (string) $xml->NFe->infNFe->dest->enderDest->xBairro;
      $this->cliente['enderDest']['cMun']    = (string) $xml->NFe->infNFe->dest->enderDest->cMun;
      $this->cliente['enderDest']['xMun']    = (string) $xml->NFe->infNFe->dest->enderDest->xMun;
      $this->cliente['enderDest']['UF']      = (string) $xml->NFe->infNFe->dest->enderDest->UF;
      $this->cliente['enderDest']['CEP']     = (string) $xml->NFe->infNFe->dest->enderDest->CEP;
      $this->cliente['enderDest']['cPais']   = (string) $xml->NFe->infNFe->dest->enderDest->cPais;
      $this->cliente['enderDest']['xPais']   = (string) $xml->NFe->infNFe->dest->enderDest->xPais;
      $this->cliente['enderDest']['fone']    = (string) $xml->NFe->infNFe->dest->enderDest->fone;
      $this->cliente['indIEDest']            = (string) $xml->NFe->infNFe->dest->indIEDest;
      $this->cliente['IE']                   = (string) $xml->NFe->infNFe->dest->IE;
      $this->cliente['email']                = (string) $xml->NFe->infNFe->dest->email;


      $this->isArmazenagem = false;
      if ($which == 'NF_SAIDA'){
        if (count($this->produtos)>0){
          foreach ($this->produtos as $key => $dados_produto){
            if ($dados_produto['CFOP']==5905){
              $this->isArmazenagem = true;
              break;
            }
          }
        }
      }

    }

    if (!$this->HasError()){
      return true;
    } else {
      return false;
    }



  }

    private function verifyDate($format, $date)
    {
        return (\DateTime::createFromFormat($format, $date) !== false);
    }


  private function InverteData($data_us){
    if ($this->verifyDate('Y-m-d', $data_us)){
      list ($ano, $mes, $dia) = explode('-', $data_us);
      return sprintf("%s/%s/%s", $dia, $mes, $ano);
    } else {
      return false;
    }
  }

}