# pad-rem-sim

Simulates Puzzles and Dragons Rare Egg Machine.
Also has tool to add up rates.

REM Data format:
A machine is divided into tiers by rates:
```
{	
	"<rem name>" :{
		{<tier>},
		{<tier>},
		...
	}
}
```
Each tier looks like this:
```
{
	"egg" : <egg img to use>,
	"title" : <title of tier>,
	"rate" : <rate, as a float>,
	"id_array" : [
		<array of ids>
	],
	"override_array" : {
		<id> : <url to replace default icon>
	}
}
```
Lastly there's a mapping that defines short names => full names
```
{
	"FullNames" :{
		"<short name>" : "<full name>",
		...
	}
}
```
# Much Thanks to:

SaladBadger for providing game assets