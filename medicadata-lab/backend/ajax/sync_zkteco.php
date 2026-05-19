<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/sdk/zkteco/vendor/autoload.php';

use Jmrashed\Zkteco\Lib\ZKTeco;

class ZkTecoController {
    private ZKTeco $_zk;
    private $_dboConnect;
    public bool $isConnected = false;

    public function __construct(string $ip, int $port, $dboConnect)
    {
        $this->_zk = new ZKTeco($ip, $port);    
        $this->_dboConnect = $dboConnect;
        
        if (isset($this->_zk->_zkclient)) {
            socket_set_option($this->_zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
            socket_set_option($this->_zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
        }
    }

    public function fetchAttendance() : mixed {
        $old_err = error_reporting();
        error_reporting($old_err & ~E_WARNING);

        $attendanceData = [];

        try {
            $this->isConnected = $this->_zk->connect();

            if ($this->isConnected) {
                $attendance = $this->_zk->getAttendance();

                if (!empty($attendance)) {
                    /* TODO: Base de datos no integrada por completo aún.
                    $stmt = $this->_dboConnect->prepare("INSERT IGNORE INTO attendance_log_employee 
                        (employeeCode, typeDailing, dTime, created_by) 
                        VALUES (:code, :type, :time, :user)");
                    */

                    foreach ($attendance as $log) {
                        $tipo = $this->mapType($log['type']);

                        /*
                        $stmt->execute([
                            ':code' => (string)$log['id'],
                            ':type' => $tipo,
                            ':time' => $log['timestamp'],
                            ':user' => 'SYSTEM_SYNC'
                        ]);
                        */

                        $log['mapped_type'] = $tipo;
                        $attendanceData[] = $log;
                    }
                }

                $this->_zk->disconnect();
                $this->isConnected = false;
                return $attendanceData;
            }
            return false;
        } catch (Exception $e) {
            return false;
        } finally {
            error_reporting($old_err);
        }
    }

    private function mapType(int $type): string {
        return match($type) {
            0 => "Entrada",
            1 => "Salida",
            4 => "Entrada_Almuerzo",
            5 => "Salida_Almuerzo",
            default => "Entrada",
        };
    }

    public function deleteBulkAttendance() : bool {
        try {
            $this->isConnected = $this->_zk->connect();
            if (!$this->isConnected) return false;
            
            $result = $this->_zk->clearAttendance();
            $this->_zk->disconnect();
            $this->isConnected = false;
            return $result;
        } catch (Exception $e) {
            $this->isConnected = false;
            return false;
        }
    }
}
