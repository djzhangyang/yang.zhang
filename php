PHP是弱类型，动态的语言脚本。在申明一个变量的时候，并不需要指明它保存的数据类型。

1
<?php 
2
$var = 1; 
3
$var = "variable"; 
4
$var = 1.00; 
5
$var = array(); 
6
$var = new Object(); 
7
?>
动态变量，在运行期间是可以改变的，并且在使用前无需声明变量类型。

那么，问题一、Zend引擎是如何用C实现这种弱类型的呢？

实际上，在PHP中声明的变量，在ZE中都是用结构体zval来保存的。首先我们打开Zend/zend.h来看zval的定义：

01
typedef struct _zval_struct zval; 
02
   
03
struct _zval_struct { 
04
    /* Variable information */ 
05
    zvalue_value value;     /* value */ 
06
    zend_uint refcount__gc; 
07
    zend_uchar type;    /* active type */ 
08
    zend_uchar is_ref__gc; 
09
}; 
10
   
11
typedef union _zvalue_value { 
12
    long lval;  /* long value */ 
13
    double dval;    /* double value */ 
14
    struct { 
15
        char *val; 
16
        int len; 
17
    } str; 
18
    HashTable *ht;  /* hash table value */ 
19
    zend_object_value obj; 
20
} zvalue_value;
Zend/zend_types.h：

1
typedef unsigned char zend_bool; 
2
typedef unsigned char zend_uchar; 
3
typedef unsigned int zend_uint; 
4
typedef unsigned long zend_ulong; 
5
typedef unsigned short zend_ushort; 
从上述代码中，可以看到_zvalue_value是真正保存数据的关键部分。通过共用体实现的弱类型变量声明。

问题二、Zend引擎是如何判别、存储PHP中的多种数据类型的呢？

_zval_struct.type中存储着一个变量的真正类型，根据type来选择如何获取zvalue_value的值。

01
type值列表(Zend/zend.h)： 
02
#define IS_NULL     0 
03
#define IS_LONG     1 
04
#define IS_DOUBLE   2 
05
#define IS_BOOL     3 
06
#define IS_ARRAY    4 
07
#define IS_OBJECT   5 
08
#define IS_STRING   6 
09
#define IS_RESOURCE 7 
10
#define IS_CONSTANT 8 
11
#define IS_CONSTANT_ARRAY   9 
来看一个简单的例子：

1
<?php 
2
    $a = 1; 
3
    //此时zval.type = IS_LONG,那么zval.value就去取lval. 
4
    $a = array(); 
5
    //此时zval.type = IS_ARRAY,那么zval.value就去取ht. 
6
?>
这其中最复杂的，并且在开发第三方扩展中经常需要用到的是"资源类型"。在PHP中，任何不属于PHP的内建的变量类型的变量，都会被看作资源来进行保存。比如：数据库句柄、打开的文件句柄、打开的socket句柄。

资源类型，需要使用ZE提供的API函数来注册，资源变量的声明和使用将在单独的篇目中进行详细介绍。正是因为ZE这样的处理方式，使PHP就实现了弱类型，而对于ZE的来说，它所面对的永远都是同一种类型zval。
