<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Benchmarks extends Controller
{

    function SQLServer(){
        
        $queries = [];
        
        $queries[] = DB::table('users')->orderBy('email', 'desc');
        $queries[] = DB::table('users');
        $queries[] = 'select * from [users] order by (SELECT 0) offset ? rows fetch next ? rows only';
        
        $results = collect();
        
        $number_of_users = 10000;
        $per_page = 10;
        $pages = $number_of_users / $per_page;
        
        shuffle($queries);
        
        foreach($queries as $query){
            
            $query_times = collect();
    
            for($page = 0; $page < $pages; $page++) {
                
                $skip = $page * $per_page;
                
                if($query instanceof Builder){
                    
                    $time_start = microtime(true);
                    $throwaway = $query->skip($skip)->take($per_page)->get();
                    $time_end = microtime(true);
                    
                } else {
                    
                    $time_start = microtime(true);
                    $throwaway = DB::select($query, [$skip, $per_page]);
                    $time_end = microtime(true);
                    
                }
             
                $query_times->add($time_end - $time_start);
                
            }
            
            $results->add([
                'sql' => $query instanceof Builder ? $query->toSql() : $query,
                'avg' => $query_times->average(),
                'max' => $query_times->max(),
                'min' => $query_times->min(),
                'median' => $query_times->median(),
                'total' => $query_times->sum(),
            ]);
            
        }
        
        return response()->view('benchmarks', ['results' => $results]);
        
    }
    
}
