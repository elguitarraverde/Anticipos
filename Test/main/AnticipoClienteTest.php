<?php


namespace FacturaScripts\Test\Plugins;


use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Divisa;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Plugins\Anticipos\Model\Anticipo;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class AnticipoClienteTest extends TestCase
{
    use LogErrorsTrait;

    protected function setUp(): void
    {
        Plugins::disable('Anticipos');

        new User();
        new Cliente();
        new Divisa();
        new AlbaranCliente();
        new FacturaCliente();
        new PedidoCliente();
        new PresupuestoCliente();
        new ReciboCliente();

        Plugins::enable('Anticipos');
    }

    public function testSePuedeCrearLaTabla()
    {
        $database = new DataBase();
        $tables = $database->getTables();

        // Borramos la tabla para ver si realmente se crea al instanciar el modelo
        if (in_array(Anticipo::tableName(), $tables)) {
            $sql = $database->getEngine()->getSQL()->sqlDropTable(Anticipo::tableName());
            $database->exec($sql);
        }

        // Instanciamos el modelo para que cree la tabla
        new Anticipo();

        $tables = $database->getTables();

        $this->assertTrue(in_array(Anticipo::tableName(), $tables));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
