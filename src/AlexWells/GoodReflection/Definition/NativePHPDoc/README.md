### Native PHPDoc

This definition provider combines native reflection, PHPDoc parser and AST node sources parser.

It works the following way:
 - it gets basic information from native reflection
 - source code then is parsed into AST to get information on which types are imported (for PHPDoc), 
what class members are declared in that class (to differentiate them from ones implicitly included
by PHP or those inherited from traits) and doc comments for trait uses (as those may use generics)
 - we then parse doc comments that native reflection returns and use that to provide
more specific types for functions, properties and inheritance

