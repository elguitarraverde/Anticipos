<?php


namespace FacturaScripts\Test\Plugins;


use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\AlbaranProveedor;
use FacturaScripts\Core\Model\Divisa;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Model\PedidoProveedor;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\ReciboProveedor;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Dinamic\Model\AnticipoP;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class AnticipoProveedorTest extends TestCase
{
    use LogErrorsTrait;

    protected function setUp(): void
    {
        Plugins::disable('Anticipos');

        new User();
        new Proveedor();
        new Divisa();
        new AlbaranProveedor();
        new FacturaProveedor();
        new PedidoProveedor();
        new PresupuestoProveedor();
        new ReciboProveedor();

        Plugins::enable('Anticipos');
    }

    public function testSePuedeCrearLaTabla()
    {
        $database = new DataBase();
        $tables = $database->getTables();

        // Borramos la tabla para ver si realmente se crea al instanciar el modelo
        if (in_array(AnticipoP::tableName(), $tables)) {
            $sql = $database->getEngine()->getSQL()->sqlDropTable(AnticipoP::tableName());
            $database->exec($sql);
        }

        // Instanciamos el modelo para que cree la tabla
        new AnticipoP();

        $tables = $database->getTables();

        $this->assertTrue(in_array(AnticipoP::tableName(), $tables));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
