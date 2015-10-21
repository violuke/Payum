<?php
namespace Payum\Core\Tests;

use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Bridge\Twig\Action\RenderTemplateAction;
use Payum\Core\CoreGatewayFactory;
use Payum\Core\HttpClientInterface;

class CoreGatewayFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldImplementCoreGatewayFactoryInterface()
    {
        $rc = new \ReflectionClass('Payum\Core\CoreGatewayFactory');

        $this->assertTrue($rc->implementsInterface('Payum\Core\GatewayFactoryInterface'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new CoreGatewayFactory();
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayWithoutAnyOptions()
    {
        $factory = new CoreGatewayFactory();

        $gateway = $factory->create(array());

        $this->assertInstanceOf('Payum\Core\Gateway', $gateway);

        $this->assertAttributeNotEmpty('apis', $gateway);
        $this->assertAttributeNotEmpty('actions', $gateway);

        $extensions = $this->readAttribute($gateway, 'extensions');
        $this->assertAttributeNotEmpty('extensions', $extensions);
    }

    /**
     * @test
     */
    public function shouldAlwaysAddHttpClientAsApi()
    {
        $factory = new CoreGatewayFactory();

        $config = $factory->createConfig(array());
        $this->assertArrayHasKey('payum.api.http_client', $config);
        $this->assertInstanceOf(\Closure::class, $config['payum.api.http_client']);

        $this->assertSame($config['payum.http_client'], $config['payum.api.http_client'](new ArrayObject($config)));
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayWithCustomApi()
    {
        $factory = new CoreGatewayFactory();

        $gateway = $factory->create(array(
            'payum.api' => new \stdClass(),
        ));

        $this->assertInstanceOf('Payum\Core\Gateway', $gateway);

        $this->assertAttributeNotEmpty('apis', $gateway);
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayConfig()
    {
        $factory = new CoreGatewayFactory();

        $config = $factory->createConfig();

        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);

        $this->assertInstanceOf('Payum\Core\HttpClientInterface', $config['payum.http_client']);
        $this->assertInstanceOf('Payum\Core\Bridge\PlainPhp\Action\GetHttpRequestAction', $config['payum.action.get_http_request']);
        $this->assertInstanceOf('Payum\Core\Action\CapturePaymentAction', $config['payum.action.capture_payment']);
        $this->assertInstanceOf('Payum\Core\Action\ExecuteSameRequestWithModelDetailsAction', $config['payum.action.execute_same_request_with_model_details']);
        $this->assertInstanceOf('Closure', $config['payum.action.render_template']);
        $this->assertInstanceOf('Payum\Core\Extension\EndlessCycleDetectorExtension', $config['payum.extension.endless_cycle_detector']);

        $this->assertEquals('@PayumCore/layout.html.twig', $config['payum.template.layout']);
        $this->assertEquals(array(), $config['payum.prepend_actions']);
        $this->assertEquals(array(), $config['payum.prepend_extensions']);
        $this->assertEquals(array(), $config['payum.prepend_apis']);
        $this->assertEquals(array(), $config['payum.default_options']);
        $this->assertEquals(array(), $config['payum.required_options']);
    }

    /**
     * @test
     */
    public function shouldConfigurePaths()
    {
        $factory = new CoreGatewayFactory();

        $config = $factory->createConfig();

        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);

        $this->assertInternalType('array', $config['payum.paths']);
        $this->assertNotEmpty($config['payum.paths']);

        $this->assertArrayHasKey('PayumCore', $config['payum.paths']);
        $this->assertStringEndsWith('Resources/views', $config['payum.paths']['PayumCore']);
        $this->assertTrue(file_exists($config['payum.paths']['PayumCore']));
    }

    /**
     * @test
     */
    public function shouldConfigurePathsPlusExtraOne()
    {
        $factory = new CoreGatewayFactory();

        $config = $factory->createConfig([
            'payum.paths' => ['FooNamespace' => 'FooPath']
        ]);

        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);

        $this->assertInternalType('array', $config['payum.paths']);
        $this->assertNotEmpty($config['payum.paths']);

        $this->assertArrayHasKey('PayumCore', $config['payum.paths']);
        $this->assertStringEndsWith('Resources/views', $config['payum.paths']['PayumCore']);
        $this->assertTrue(file_exists($config['payum.paths']['PayumCore']));

        $this->assertArrayHasKey('FooNamespace', $config['payum.paths']);
        $this->assertEquals('FooPath', $config['payum.paths']['FooNamespace']);
    }

    /**
     * @test
     */
    public function shouldConfigureTwigEnvironmentGatewayConfig()
    {
        $factory = new CoreGatewayFactory();

        $config = $factory->createConfig();

        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);

        $this->assertInstanceOf(\Closure::class, $config['twig.env']);

        $twig = call_user_func($config['twig.env'], ArrayObject::ensureArrayObject($config));

        $this->assertInstanceOf(\Twig_Environment::class, $twig);
    }

    /**
     * @test
     */
    public function shouldConfigureRenderTemplateAction()
    {
        $factory = new CoreGatewayFactory();

        $twig = new \Twig_Environment();

        $config = $factory->createConfig([
            'twig.env' => $twig,
        ]);

        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);

        $this->assertInstanceOf(\Closure::class, $config['payum.action.render_template']);

        $action = call_user_func($config['payum.action.render_template'], ArrayObject::ensureArrayObject($config));
        $this->assertInstanceOf(RenderTemplateAction::class, $action);
        $this->assertAttributeSame($twig, 'twig', $action);

        $this->assertSame($twig, $config['twig.env']);
    }

    /**
     * @test
     */
    public function shouldAddDefaultConfigPassedInConstructorWhileCreatingGatewayConfig()
    {
        $factory = new CoreGatewayFactory(array(
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ));

        $config = $factory->createConfig();

        $this->assertInternalType('array', $config);

        $this->assertArrayHasKey('foo', $config);
        $this->assertEquals('fooVal', $config['foo']);

        $this->assertArrayHasKey('bar', $config);
        $this->assertEquals('barVal', $config['bar']);
    }

    /**
     * @test
     */
    public function shouldAllowPrependAction()
    {
        $firstAction = $this->getMock('Payum\Core\Action\ActionInterface');
        $secondAction = $this->getMock('Payum\Core\Action\ActionInterface');

        $factory = new CoreGatewayFactory();

        $gateway = $factory->create(array(
            'payum.action.foo' => $firstAction,
            'payum.action.bar' => $secondAction,
        ));

        $actions = $this->readAttribute($gateway, 'actions');
        $this->assertSame($firstAction, $actions[0]);
        $this->assertSame($secondAction, $actions[1]);

        $gateway = $factory->create(array(
            'payum.action.foo' => $firstAction,
            'payum.action.bar' => $secondAction,
            'payum.prepend_actions' => array(
                'payum.action.bar'
            )
        ));

        $actions = $this->readAttribute($gateway, 'actions');
        $this->assertSame($secondAction, $actions[0]);
        $this->assertSame($firstAction, $actions[1]);
    }

    /**
     * @test
     */
    public function shouldAllowPrependApi()
    {
        $firstApi = new \stdClass();
        $secondApi = new \stdClass();

        $factory = new CoreGatewayFactory();

        $gateway = $factory->create(array(
            'payum.api.foo' => $firstApi,
            'payum.api.bar' => $secondApi,
        ));

        $apis = $this->readAttribute($gateway, 'apis');
        $this->assertSame($firstApi, $apis[0]);
        $this->assertSame($secondApi, $apis[1]);

        $gateway = $factory->create(array(
            'payum.api.foo' => $firstApi,
            'payum.api.bar' => $secondApi,
            'payum.prepend_apis' => array(
                'payum.api.bar'
            )
        ));

        $apis = $this->readAttribute($gateway, 'apis');
        $this->assertSame($secondApi, $apis[0]);
        $this->assertSame($firstApi, $apis[1]);
    }

    /**
     * @test
     */
    public function shouldAllowPrependExtensions()
    {
        $firstExtension = $this->getMock('Payum\Core\Extension\ExtensionInterface');
        $secondExtension = $this->getMock('Payum\Core\Extension\ExtensionInterface');

        $factory = new CoreGatewayFactory();

        $gateway = $factory->create(array(
            'payum.extension.foo' => $firstExtension,
            'payum.extension.bar' => $secondExtension,
        ));

        $extensions = $this->readAttribute($this->readAttribute($gateway, 'extensions'), 'extensions');
        $this->assertSame($firstExtension, $extensions[0]);
        $this->assertSame($secondExtension, $extensions[1]);

        $gateway = $factory->create(array(
            'payum.extension.foo' => $firstExtension,
            'payum.extension.bar' => $secondExtension,
            'payum.prepend_extensions' => array(
                'payum.extension.bar'
            )
        ));

        $extensions = $this->readAttribute($this->readAttribute($gateway, 'extensions'), 'extensions');
        $this->assertSame($secondExtension, $extensions[0]);
        $this->assertSame($firstExtension, $extensions[1]);
    }
}