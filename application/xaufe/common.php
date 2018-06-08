<?php
function sjdToTime($sjd)
{
	if ($sjd == 1)
		return 8;
	else if ($sjd == 2)
		return 10;
	else if ($sjd == 3)
		return 14;
	else if ($sjd == 4)
		return 16;
	else if ($sjd == 5)
		return 19;
	else if ($sjd == 6)
		return 20;
	else return 0;
}