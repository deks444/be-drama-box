pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        APP_PORT = '9004'
        PHP_VERSION = '8.2' // Sesuaikan dengan versi PHP Anda
    }

    stages {
        stage('Checkout') {
            steps {
                // Memastikan workspace bersih sebelum mulai
                cleanWs()
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                // Menggunakan double quotes untuk Jenkins, tapi single quotes untuk 'sh' 
                // agar variabel dihandle oleh Shell, bukan Groovy.
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'ENV_PATH')]) {
                    sh '''
                        if [ -f "$ENV_PATH" ]; then
                            cp "$ENV_PATH" .env
                            echo "File .env berhasil disalin."
                        else
                            echo "Error: Source credential file tidak ditemukan!"
                            exit 1
                        fi
                    '''
                }
            }
        }

        stage('Install & Deploy') {
            steps {
                script {
                    // Menggabungkan install dan run untuk memastikan context direktori sama
                    sh '''
                        composer install --no-interaction --optimize-autoloader
                        
                        export JENKINS_NODE_COOKIE=dontKillMe
                        
                        # Kill proses lama di port 9004 jika ada
                        fuser -k ${APP_PORT}/tcp || true
                        
                        # Jalankan di background
                        nohup php artisan serve --host=0.0.0.0 --port=${APP_PORT} > laravel_logs.log 2>&1 &
                        
                        echo "Aplikasi berjalan di background pada port ${APP_PORT}"
                    '''
            }
        }
    }
}

    post {
        success {
            echo "Aplikasi berhasil berjalan di port ${APP_PORT} dalam mode background."
        }
        failure {
            echo "Pipeline gagal. Silakan periksa log Jenkins."
        }
    }
}
