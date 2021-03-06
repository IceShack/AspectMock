<?php
namespace AspectMock\Proxy;
use AspectMock\Core\Registry;
use AspectMock\Test;

/**
 * InstanceProxy is a proxy for underlying object, mocked with test::double.
 * A real object can be returned with `getObject` methods.
 *
 * ``` php
 * <?php
 * $user1 = new User;
 * $user2 = test::double($user1);
 * $user1 instanceof User; // true
 * $user2 instanceof AspectMock\Proxy\InstanceProxy; // true
 *
 * $user1 === $user2->getObject(); // true
 *
 * ?>
 * ```
 *
 * Contains verification methods and `class` property that points to `ClassProxy`.
 *
 * ``` php
 * <?php
 * $user = new User(['name' => 'davert']);
 * $user = test::double(new User);
 * // now $user is a proxy class of user
 * $this->assertEquals('davert', $user->getName()); // success
 * $user->verifyInvoked('getName'); // success
 * $this->assertInstanceOf('User', $user); // fail
 * ?>
 * ```
 *
 * A `class` property allows to verify method calls to any instance of this class.
 * Constains a **ClassVerifier** object.
 *
 * ``` php
 * <?php
 * $user = test::double(new User);
 * $user->class->hasMethod('save');
 * $user->setName('davert');
 * $user->class->verifyInvoked('setName');
 * ?>
 * ```
 *
 * Class InstanceVerifier
 * @package AspectMock\Core
 * @property $class ClassProxy
 */

class InstanceProxy extends Verifier {

    protected $instance;

    public function __construct($object)
    {
        $this->instance = $object;
        $this->className = get_class($object);
    }

    protected function callSyntax($method)
    {
        return "->";
    }

    /**
     * Returns a real object that is proxified.
     *
     * @return mixed
     */
    public function getObject()
    {
        return $this->instance;
    }
    
    protected function getCallsForMethod($method)
    {
        $calls = Registry::getInstanceCallsFor($this->instance);
        return isset($calls[$method])
            ? $calls[$method]
            : array();
    }
    

    // proxify calls to the methods
    public function __call($method, $args)
    {
        if (method_exists($this->instance, $method)) {
            return call_user_func_array(array($this->instance, $method), $args);
        }
        if (method_exists($this->instance, '__call')) {
            return call_user_func(array($this->instance, '__call'), $method, $args);
        }
    }

    public function __get($property)
    {
        if ($property === 'class') {
            return $this->class = new ClassProxy($this->className);
        }
        if (method_exists($this->instance, '__get')) {
            return call_user_func(array($this->instance, '__get'), $property);
        }
        return $this->instance->$property;
    }

    public function __set($property, $value)
    {
        $this->instance->$property = $value;
    }
    
}