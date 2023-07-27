<?php

namespace FacturaScripts\Plugins\Anticipos\Extension\Model\Base;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Lib\ReceiptGenerator;
use FacturaScripts\Core\Model\DocTransformation;
use FacturaScripts\Core\Model\EstadoDocumento;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Plugins\Anticipos\Model\Anticipo;

/**
 * Description of SalesDocument
 *
 * @property $idestado
 * @method primaryColumnValue()
 * @method primaryColumn()
 * @method modelClassName()
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 * @author Jorge-Prebac              <info@prebac.com>
 */
class SalesDocument
{
	public $advance;
	
	public function clear(): Closure
	{
		return function () {
			$this->getAdvance('advance');
			return;
		};
	}

	public function getAdvance(): Closure
	{
		return function ($field) {
			
			$pCl = $this->primaryColumn();
			
			if (false === (empty($this->{$field}))) {
				return;
			}

			$anticipoModel = new Anticipo();
			$anticipos = $anticipoModel->all([], [], 0, 0);

			if (false === (count($anticipos) != 0)) {
				return;
			}

			$sql = 'UPDATE ' . ($this->tableName()) . ' SET advance = (SELECT COUNT(' . $pCl . ') FROM anticipos WHERE anticipos.' . $pCl . ' = ' . ($this->tableName()) . '. ' . $pCl . ');';
			
			if (false === (self::$dataBase->exec($sql))) {
				return ($this->toolBox()->i18nLog()->warning('record-save-error'));
			}
		};
	}

    public function saveUpdate(): Closure
    {
        return function () {
            $estado = new EstadoDocumento();
            $estado->loadFromCode($this->idestado);

            if (empty($estado->generadoc)) {
                return;
            }

			$whereAnticipos = [new DataBaseWhere($this->primaryColumn(), $this->primaryColumnValue())];
            $anticipos = (new Anticipo())->all($whereAnticipos, [], 0, 0);

            if (count($anticipos) === 0) {
                return;
            }

            $primaryColumns = [
                'AlbaranCliente' => 'idalbaran',
                'FacturaCliente' => 'idfactura',
                'PedidoCliente' => 'idpedido',
                'PresupuestoCliente' => 'idpresupuesto',
            ];

            $whereTransformation = [
				new DataBaseWhere('model1', $this->modelClassName()),
                new DataBaseWhere('iddoc1', $this->primaryColumnValue())
            ];

            $transformation = new DocTransformation();
            $transformation->loadFromCode('', $whereTransformation, ['iddoc2' => 'DESC']);

            if (!$transformation->model2 && !$transformation->iddoc2) {
				return;
            }

            foreach ($anticipos as $anticipo) {
                $anticipo->{$primaryColumns[$transformation->model2]} = $transformation->iddoc2;

                if (false === $anticipo->save()) {
                    ToolBox::i18nLog('Anticipos Cli')->warning('record-save-error' );
                    return;
                }
            }

            //Si es una Factura generamos los recibos correspondientes.
            if ('FacturaCliente' === $transformation->model2 && $transformation->iddoc2) {
                $factura = new FacturaCliente();

                if (false === $factura->loadFromCode($transformation->iddoc2)) {
                    return;
                }

                //Eliminamos el recibo generado automaticamente.
                foreach ($factura->getReceipts() as $recibo) {
                    $recibo->delete();
                }

                //Generamos los nuevos recibos en base a los anticipos.
                $numero = 1;
				foreach ($anticipos as $anticipo) {
					$recibo = new ReciboCliente();

					$recibo->codcliente = $anticipo->codcliente;
					$recibo->coddivisa = $anticipo->coddivisa;
					$recibo->idempresa = $anticipo->idempresa;
					$recibo->idfactura = $anticipo->idfactura;
					$recibo->importe = $anticipo->importe;
					$recibo->nick = $anticipo->user;
					$recibo->numero = $numero++;
					$recibo->fecha = $anticipo->fecha;
					$recibo->codpago = $anticipo->codpago;
					$recibo->observaciones = $anticipo->nota;
					$recibo->pagado = 1;
					$recibo->vencimiento = $factura->fecha;
					if (true === $this->toolBox()->appSettings()->get('anticipos', 'pdAnticipos')) {
						$recibo->fechapago = $anticipo->fecha;
						$recibo->vencimiento = $anticipo->fecha;
					}
					$recibo->save();
				}

                //Generamos el recibo por el saldo pendiente si ubiese y actualizamos la factura.
                $generator = new ReceiptGenerator();
                $generator->generate($factura);
                $generator->update($factura);
            }
        };
    }
}