# Moody PHP Preprocessor

## What is Moody?

Moody is a small scripting language specially designed for preprocessing PHP sources. 
Moody itself also contains some modules used for optimizing PHP code, without the need to write any new code. 
It will just give you an optimized version of your PHP code. At the moment there are not too many 
automatic optimization modules, but they will be more in the future. :-)

## How to write code in Moody?

Moody code is written in PHP comments. Look at these examples of Moody code:

```php
    <?php
     #.myVar = 7
     #.if myVar > 8
      echo 'Hello ';
     #.endif
     echo 'World!;'
    ?>
```

When preprocessed using Moody, the following code would be generated:

```php
     <?php
       echo 'World!';
     ?>
```

This example is somewhat useless, but let's say the variable would have a dynamic 
value (for example obtained through the programs' configuration) and the code in the if block would make 
a little bit more sense, you could remove unnecessary code from the compiled program. This will save you a 
check for a specific configuration value on each iteration and - more important in such examples - the RAM used up 
for simply having code loaded that will never be run anyway.

You can call any PHP function or static method (objects currently not supported) from Moody:

```php
     <?php
      #.randomValue = #.rand 0, 1

      #.if randomValue == 1
        echo 'Do it!';
      #.else
        echo 'Better not do it!';
      #.endif
     ?>
```

Moody will parse PHP namespaces and PHP constants and even substitute read occurences in the generated code:

```php
     <?php
       namespace MyNamespace {
         const namespacedConst = "abc";

         $someVar = namespacedConst;
       }
 
       namespace {
         const myConst = 8;

         #.moodyConst = 123

         $x = myConst;
         $y = MyNamespace\namespacedConst;
         $z = moodyConst;
       }
     ?>
```

This would generate the following code:

```php
     <?php
       namespace MyNamespace {
         const namespacedConst = "abc";

         $someVar = "abc";
       }
  
       namespace {
         const myConst = 8;

         $x = 8;
         $y = "abc";
         $z = 123;
       }
     ?>
```

This also gives you a small performance improvement since the constant does not have to be resolved at script 
runtime anymore.

## Contact

If you got questions about Moody or just want to learn more, feel free to contact me at [dev@pp3345.net](mailto:dev@pp3345.net). :-)
