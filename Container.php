<?php
/**
 * 
 * IOC;
 * @author mantou
 *
 */
namespace IO;
class Container implements \ArrayAccess{
    
    /**
     * 容器
     * @var unknown
     */
    protected static $maps = [];
    
    /**
     * 获取
     * @param unknown $offset
     */
    public function get($offset){
        return $this->offsetGet($offset);
    }
    
    /**
     * 检查
     * @param unknown $offset
     */
    public function has($offset){
        return $this->offsetExists($offset);
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return isset( self::$maps[$offset] );
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {   
        //闭包处理
        $class = self::$maps[$offset];
        if( $class instanceof \Closure ) return $class($this);

        //值处理
        if( !is_object($class) ) return $class;
        
        //类处理
        $reflector = new \ReflectionClass($class);
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Can't instantiate this.");
        }
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $class();
        }
        
        //自动依赖注入
        return $reflector->newInstanceArgs(function()use($constructor){
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                $dependency = $parameter->getClass();
                if(!is_null( $dependency )) {
                    $dependencies[] = $this->offsetGet($dependency->name);
                    continue;
                }
                if( $parameter->isDefaultValueAvailable() ){
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new \Exception("Error");
            }
            return $dependencies;
        });
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        self::$maps[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        unset( self::$maps[$offset] );
        
    }

}