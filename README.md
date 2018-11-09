# pad-rem-sim

Simulates Puzzles and Dragons Rare Egg Machine.
Also has tool to add up rates.

REM Data format:
A machine is divided into tiers by rates:
```
{	"items" :{
	{<tier>},
	{<tier>},
	...
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
