Arquitectura Hexagonal en un proyecto Symfony: Trabajando con Identificadores de Dominio
=====================
Cuando hablamos de Identificadores de Dominio, estamos hablando del ID de una entidad de
nuestro dominio. Muchas veces, puede parecer un simple trámite asignar un ID a una instancia
de una entidad de dominio, y por eso en muchos casos se designa esta repetitiva tarea al
ORM que estemos utilizando (Doctrine en nuestro caso).

En este artículo queremos destacar la importancia de realizar la asignación del ID desde dentro
de nuestro propio dominio, demostrar los beneficios que nos aporta y comprender los problemas
que nos puede proporcionar designar esta tarea a un agente externo a nuestro dominio tal como
sería un ORM(Doctrine).

Antes de proseguir, te recomiendo, si aún no lo has hecho, que eches un vistazo al primer artículo
sobre arquitectura hexagonal en el [**blog de ApiumHub**][1], puesto que se definen algunos de los
conceptos que vamos a tratar a continuación como *Testeabilidad*, *Independencia del framework*,
*Independencia de cualquier Agente externo*, etc.

Por qué deberíamos asignar el ID en Dominio?
================
Basándome en mi experiencia, muchos de los proyectos PHP con Symfony en los que he trabajado, se delega
la tarea de asignar el ID a la entidad en el ORM. Esto a priori puede darnos ciertos problemas, por ejemplo
nos dificulta la testeabilidad muchísimo, puesto que delegamos una parte de la construcción de nuestra
entidad a un agente externo.

Para comprender mejor lo que estoy dicendo, vamos a verlo más claramente en un ejemplo. Vamos a rescatar el ejemplo
de la entidad producto que teniamos en el primer artículo en el que hablamos sobre Arquitectura Hexagonal:
``` php
class Product
{
    private $id;

    private $name;

    private $reference;

    private $createdAt;

    private function __construct(
        string $name,
        string $reference
    )
    {
        $this->name = $name;
        $this->reference = $reference;
        $this->createdAt = new DateTime();
    }

    public static function fromDto(CreateProductRequestDto $createProductResponseDto): Product
    {
        return new Product($createProductResponseDto->name(), $createProductResponseDto->reference());
    }

    public function toDto()
    {
        return new CreateProductResponseDto($this->id, $this->name, $this->reference, $this->createdAt);
    }
}
```

Esta clase actualmente tiene un fichero de configuración en formato yml en el cual se declara el mapeo
a la base de datos, y además se ha configurado la asignación automática del ID de forma incremental:
```yaml
ProductBundle\Domain\Product:
  type: entity
  fields:
    id:
      type: integer
      id: true
      generator:
        strategy: AUTO

    name:
      type: string

    reference:
      type: string

  lifecycleCallbacks: {}
```
Tal y como podemos observar, se está asignando de forma automatica e incremental un ID de tipo integer. Aquí a simple vista,
podemos observar 2 problemas:
* **Dificulta la testeabilidad**: Realizando tests unitarios, el ID de nuestra entidad siempre tendrá un valor NULL,
puesto que no tendremos un ID en nuestra entidad hasta que no se persista en la base de datos, cosa que por definición nunca debería ser así.
* **Dependencia de agentes externos**: En el momento que se construye una instancia de la entidad, dicha instancia tiene sentido por si misma dentro de nuestro dominio,
por lo tanto debería tener un ID asignado desde el momento de su creación, independientemente de si ha sido persistida o no en la base de datos
(Que sería el momento en el que se realizaría la asignación del ID con la configuración actual).
Una instancia de una clase debería ser correcta en el mismo momento en el que ésta se crea, puesto que en el propio constructor
tendremos todas las comprobaciones necesarias para permitir su creación. En el escenario inicial, esto no sucede así, puesto
que hasta que no persistamos la entidad, ésta no será completa, queriendo decir que pese a haber finalizado su construcción,
dicha entidad se encuentra en un estado aún incompleto, y en consecuencia, erróneo.

Además de romper con estos dos principios de buenas prácticas sobre arquitectura hexagonal,
tenemos un claro problema de seguridad. En caso de que queramos exponer los datos de nuestro producto accediendo a estos
mediante su ID, estamos exponiendo un id de tipo integer autoincremental. De manera que cualquier usario de nuestra API
podría acceder a todos los datos de nuestros productos. Vamos a intentar solventar tambien este problema.
En último lugar, dejando de utilizar ids con integers simples también solventaríamos un posible problema de colisiones en
el caso de que trabajasemos con varios servidores a la vez sobre una misma base de datos.

Así pues, vamos a intentar mejorar nuestro código, aplicando de forma iterativa los conceptos definidos en el artículo anterior
sobre nuestra entidad de dominio.

Eliminar agentes externos
-----
Primero de todo debemos abstraer nuestro dominio de cualquier agente o dependencia externa, en estre caso, vamos a mover
la generación del ID de la entidad al constructor de la entidad, en lugar de hacerlo desde la configuración en el fichero yaml:
```php
private function __construct(
    string $name,
    string $reference
)
{
    $this->id = Uuid::uuid4();
    $this->name = $name;
    $this->reference = $reference;
    $this->createdAt = new DateTime();
}
```
```yaml
ProductBundle\Domain\Product:
  type: entity
  fields:
    id:
      type: string
      id: true

    name:
      type: string

    reference:
      type: string

  lifecycleCallbacks: {}
```
De esta forma, logramos abstraernos de la dependencia externa que nos otorga el ORM. Y el campo *id*
queda definido como identificador único de tipo string, pero la generación de éste viene dada
desde el dominio. 

Además, al utilizar id de tipo **UUID**, eliminamos el acceso a los productos con ids de forma secuencial,
y garantizamos que sea casi imposible acceder a otros productos que no deseemos exponer al usuario, 
así como posibles colisiones entre id's.

Mejorando la Testeabilidad
--------------------
Aún así, a simple vista podemos reconocer que la generación del ID, pese a estar dentro de nuestro dominio,
no está en el mejor lugar posible. En este momento, tampoco podríamos testear correctamente la entidad,
dado que no tenemos forma de precedir el estado final del objeto una vez creado, puesto que el ID
se genera de forma aleatoria dentro del constructor de la propia entidad.

Además, solo podríamos crear objetos nuevos, pero no hidratar objetos dados desde 
la base de datos, este fallo nos indica que la generación del ID no está
en el lugar apropiado. 

Antes de nada, vamos a realizar el Test para verificar que el comportamiento de la construcción 
de la entidad es el esperado:
```php
public function testProductDomainEntity()
{
    $createRequestDto = new CreateProductRequestDto('nameTest', 'reference-124');
    $product = Product::fromDto($createRequestDto);
    $result = $product->toDto();

    $expected = new CreateProductResponseDto(Uuid::uuid4(), 'nameTest', 'reference-124', new DateTime());

    self::assertEquals($expected, $result);
}
```
Como podemos observar, tal y como está definido el constructor privado, la entidad se construye a través del método estático
*fromDto*, con el DTO CreateProductRequestDto. Finalmente, para acceder a los datos que deseamos
mostrar de nuestra entidad, debemos realizar la transformación mediante el métod toDto() para poder acceder
a los datos que deseamos exponer de nuestra entidad. Como ya podréis suponer, el test no pasa puesto que tanto el **id** 
como el **createdAt** no coinciden.

Esto nos hace pensar que para mejorar la testeabilidad debemos extraer la creación de ambos campos de nuestra entidad:
```php
private function __construct(
    Uuid $id,
    string $name,
    string $reference,
    DateTime $createdAt
)
{
    $this->id = $id->toString();
    $this->name = $name;
    $this->reference = $reference;
    $this->createdAt =$createdAt;
}

public static function fromDto(CreateProductRequestDto $createProductResponseDto): Product
{
    return new Product(
        $createProductResponseDto->id(),
        $createProductResponseDto->name(),
        $createProductResponseDto->reference(),
        $createProductResponseDto->createdAt()
    );
}
```

Como podéis observar, se han añadido los campos id y createdAt a CreateProductRequestDto.
Esto provoca que tengamos que realizar unos pequeños cambios en nuestro test:
```php
public function testProductDomainEntity()
{
    $productId = Uuid::uuid4();
    $createdAt = new DateTime();
    $createRequestDto = new CreateProductRequestDto($productId,'nameTest', 'reference-124', $createdAt);
    $product = Product::fromDto($createRequestDto);
    $result = $product->toDto();

    $expected = new CreateProductResponseDto($productId->toString(), 'nameTest', 'reference-124', $createdAt);

    self::assertEquals($expected, $result);
}
```
Así tenemos el test en verde, gracias a la extracción de los campos Id y createdAt, que
nos permite definir en el test el resultado esperado antes de la ejecución de éste.

Como podemos observar, aún habiendo extraido la dependencia de Doctrine en la generación de nuestro ID, ahora
seguimos teniendo una dependencia sobre una librería externa, para generar el UUID. Si queremos ir un poco más lejos
y mejorar la abstracción de la dependencia de nuestro dominio de agentes extrenos, podemos crear nuestro propio ID de dominio,
en el cual encapsularemos la dependencia de la librería externa dentro de este *value object*:
```php
class ProductId
{
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function generate(): ProductId
    {
        return new self(Uuid::uuid4()->toString());
    }

    public function build(string $id): ProductId
    {
        if (Uuid::isValid($id)) {
            return new self($id);
        } else {
            throw new InvalidIdFormatException("Invalid ProductId format: ".$id);
        }
    }

    public function value(): string
    {
        return $this->id;
    }
}
```
Tendremos que refactorizar nuestra entidad de dominio y el *createProductRequestDto* para eliminar la dependencia de la libreria de UUID:
```php
class Product
{
    private $id;

    private $name;

    private $reference;

    private $createdAt;

    private function __construct(
        ProductId $id,
        string $name,
        string $reference,
        DateTime $createdAt
    ) {
        $this->id = $id->toString();
        $this->name = $name;
        $this->reference = $reference;
        $this->createdAt =$createdAt;
    }

    public static function fromDto(CreateProductRequestDto $createProductResponseDto): Product
    {
        return new Product(
            $createProductResponseDto->id(),
            $createProductResponseDto->name(),
            $createProductResponseDto->reference(),
            $createProductResponseDto->createdAt()
        );
    }

    public function toDto()
    {
        return new CreateProductResponseDto($this->id, $this->name, $this->reference, $this->createdAt);
    }
}
```

```php
class CreateProductRequestDto
{
    private $id;

    private $name;

    private $reference;

    private $createdAt;

    public function __construct(ProductId $id, string $name, string $reference, DateTime $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->reference = $reference;
        $this->createdAt = $createdAt;
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function createdAt(): DateTime
    {
        return $this->createdAt;
    }
}
```

Por último, modificar nuestro test con el nuevo identificador de dominio:
```php
class ProductTest extends TestCase
{
    public function testProductDomainEntity()
    {
        $productId = ProductId::generate();
        $createdAt = new DateTime();
        $createRequestDto = new CreateProductRequestDto($productId,'nameTest', 'reference-124', $createdAt);
        $product = Product::fromDto($createRequestDto);
        $result = $product->toDto();

        $expected = new CreateProductResponseDto($productId, 'nameTest', 'reference-124', $createdAt);

        self::assertEquals($expected, $result);
    }
}
```
Con esta última iteración hemos reducido la dependecia de la librería externa UUID a un único punto en nuestro dominio,
dentro del *value object* **ProductId**. De forma que si en el futuro deseamos modificar el formato de los identificadores
de nuestro dominio, solo debemos cambiar la dependencia en un único punto de nuestro dominio.

Conclusiones
----
Con este sencillísimo ejemplo, hemos podido demostrar los problemas que nos aporta una 
de las prácticas más extendidas y cuotidianas en los proyectos PHP. Sin embargo, tal y como
hemos podido observar, es una práctica que debemos evitar.
Como ya vimos en el artículo anterior, es muy importante aislar nuestro dominio de cualquier
dependencia externa, manteniendo nuestro dominio puro y desacoplado.

Con esta práctica, además de minimizar la dependencia de nuestro dominio respecto a un agente externo
a un único y controlado punto, hemos logrado testear al 100% nuestra entidad, y poder preveer el resultado final de la construcción de la
entidad, asegurando así un correcto comportamiento.

En último lugar, hemos mejorado la seguridad de nuestros datos, puesto que hemos evitado la exposición de datos
que no se deseen exponer evitando tener IDs secuenciales.

[1]:  https://apiumhub.com/es/tech-blog-barcelona/aplicando-arquitectura-hexagonal-proyecto-symfony/