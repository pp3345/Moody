<?
// .def("ABC", 7)
// .ifdef("ABC")
 echo 'Notch';
// .endif
// .ifdef("SOME_UNDEFINED_CONSTANT")
 echo 'This should never show up in the code';
// .endif
// .ifdef("ABC")
 // .def("BEST_SOFTWARE", "Pancake")
 // .ifdef("BEST_SOFTWARE")
 echo 'Nested ifs are working! :D';
  // .ifdef("TROLL")
   echo 'Not good.';
  // .else
   echo 'Else is working! :D';
 // .endif
// .endif
// Normal comment
echo 'Some Code';
echo /* .constant("ABC") */;
// .undefine("ABC")
// .label("someLabel")
// .ifdef("ABC")
 echo 'Trolol';
// .endif
// .ifndef("ABC")
 // .def("ABC", 'Pancake')
 echo /* .constant('ABC') */;
// .endif
// .ifndef("JUMPED_TO_SOMELABEL")
 // .def("JUMPED_TO_SOMELABEL", true)
 // .goto("someLabel")
// .endif
// .if(JUMPED_TO_SOMELABEL == 1)
 echo 'if is working :D';
 // .if(JUMPED_TO_SOMELABEL !== 1)
  echo 'if is really working :D';
 // .endif
// .endif
// .if(PHP_OS == "WINNT")
 echo 'You are using Windows! :O';
// .endif
// .if(/* .constant("T_LNUMBER") */ == 305)
 echo 'Inline instructions are working :D';
// .endif
// .if(/* .isDefined("PHP_OS") */)
 echo '.isDefined = working.';
// .endif
// .halt
// .unhandledInstruction
?>
