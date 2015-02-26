# Router.php

###Some characteristics and modificators:

    Special:
        \p -> null value

    Numbers:
        \b -> number of Boole (true/false)
        \n -> natural number
        \i -> integer
        \f -> float
        Operators:
            o -> octal number, for simple: \no; available: n,i,f
            h -> hexadecimal number, for simple: \nh; available: n,i,f
            + -> positive, for simple: \i+; available: n,i,f
            - -> negative, for simple: \i-; available: i,f
            (a;b) -> open interval from a to b, for simple: \n(100;200); available: n,i,f
                i means positive infinity (optional i+)
                i- means negative infinity
                n means no terms, for simple: \n(n;10) means n<10
            <a;b> -> close interval from a to b, for simple: \n<21;53>; available: n,i,f
            <a;b) or (a;b> -> derivative of the above, for simple: \i<0;15); available: n,i,f
                i means positive infinity (optional i+), only with open interval
                i- means negative infinity, only with open interval
                n means no terms, for simple: \n<8;n) means n>8, only with open interval
            <a -> less than a, for simple: \n<5; available: n,i,f
            <=a -> less equal to a, for simple: \n<=15; available: n,i,f
            >a -> more than a, for simple: \n>2; available: n,i,f
            >=a -> more equal to a, for simple: \n>=34; available: n,i,f
            =a -> equal to a, for simple: \i=5; available: b,n,i,f

    Characters:
        \c -> single character
        \s -> string
        Operators:
            : -> string equal to s, for simple: \c:s it means that variable is equal to 's'; available: c,s
            (a;b) -> length of string is from open interval from a to b, for simple: \s(12;14); available: s
                i means positive infinity (optional +i)
                n means no terms, for simple: \s(3;n) means lenght(s)>3
            <a;b> -> length of string is from  close interval from a to b, for simple: \s<1;5>; available: s
            <a;b) or (a;b> -> derivative of the above, for simple: \s<8;9); available: s
                i means positive infinity (optional +i), only with open interval
                n means no terms, for simple: \s<63;n) means lenght(s)>63, only with open interval
            <a -> length of string is less than a, for simple: \s<5, available: s
            <=a -> length of string is less equal to a, for simple: \s<=90; available: s
            >a -> length of string is more than a, for simple: \s>18; available: s
            >=a -> length of string is more equal to a, for simple: \s>=14; available: s
            =a -> length of string is equal to a, for simple: \s=39; available: s

###Decode link:

```php
    Router::decodeLink($_SERVER['REQUEST_URI']);
```
or
```php
    Router::decodeLink('some/link',Router::ROUTER_POST);
```

###Declare view:

```php
    $view=array(
      'basic'=>array(
        'value'=>'\i+',
        'null'=>'\p'
      ), # 'basic'
      'basic/more'=>array(
        'param'=>'\s<10'
      ) # 'basic/more'
    ); # $view
```

###Check link

```php
		use ventaquil\Router;
		
    if(Router::checkParams($view)){
      if(Router::page('basic')){
				// code
			} # if()
			elseif(Router::page('basic/more')){
				// it works!
			} # elseif()
			elseif(Router::page('basic/unknownpage')){
				// it works too!
			} # elseif()
    } # if()
```
