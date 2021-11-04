<?php
pcntl_async_signals(true);
// 进程数量扩展限制
$iForkMaxWorker  = 3;
$aPidArr = [];
while ($iForkMaxWorker > 0 && count($aPidArr) < 3) {
    $iRetry = 0;
    do {
        $iPid = pcntl_fork();
        if ($iPid > 0) {
            $aPidArr[$iPid] = $iPid;
            $iWaitRes = 0;
            cli_set_process_title("master_".posix_getpid());
            pcntl_signal(SIGCHLD, function () use ($iPid,&$iWaitRes,$aPidArr) {
                echo "收到".$iPid."子进程退出".PHP_EOL;
                $iWaitRes = pcntl_waitpid($iPid, $status, WNOHANG);
                echo "退出的子进程进程号 $iWaitRes,status为$status".PHP_EOL;
                if ($iWaitRes > 0) {
                    $aFile = array_flip($aPidArr);
                    unset($aFile[$iPid]);
                    $iPid = pcntl_fork();
                    if ($iPid > 0) {
                        $aPidArr[$iPid] = $iPid;
                    } elseif ($iPid === 0) {
                        cli_set_process_title("child_".posix_getpid());
                        echo "创建子进程success".PHP_EOL;
                        sleep(1);
                        exit;
                    } else {
                        echo "fork error".PHP_EOL;
                    }

                }
            });
            sleep(1000);
        } elseif ($iPid === 0) {
            cli_set_process_title("child_".posix_getpid());
            echo "创建子进程$iForkMaxWorker".PHP_EOL;
            sleep(1);
            exit;
        }else {
            $iRetry++;
            echo "fork error".PHP_EOL;
        }
        $iForkMaxWorker--;
    } while($iPid < 0 && $iRetry <= 3);
}