pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        APP_PORT = '9004'
        // Menambahkan lokasi umum bin ke PATH (sesuaikan jika perlu)
        PATH = "/usr/local/bin:/usr/bin:/bin:${env.PATH}"
    }

    stages {
        stage('Checkout') {
            steps {
                cleanWs()
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'ENV_PATH')]) {
                    sh '''
                        cp "$ENV_PATH" .env
                        
                        # Cek apakah composer ada, jika tidak, download lokal
                        if ! command -v composer >/dev/null 2>&1; then
                            echo "Composer tidak ditemukan, mengunduh composer.phar..."
                            curl -sS https://getcomposer.org/installer | php
                            chmod +x composer.phar
                            mv composer.phar composer
                        fi
                    '''
                }
            }
        }

        stage('Install & Deploy') {
            steps {
                script {
                    sh '''
                        # Gunakan composer lokal jika ada, jika tidak gunakan sistem
                        COMPOSER_BIN=$(command -v ./composer || command -v composer)
                        
                        $COMPOSER_BIN install --no-interaction --optimize-autoloader
                        
                        export JENKINS_NODE_COOKIE=dontKillMe
                        
                        # Bersihkan port 9004
                        fuser -k ${APP_PORT}/tcp || true
                        
                        # Jalankan Laravel
                        nohup php artisan serve --host=0.0.0.0 --port=${APP_PORT} > laravel_logs.log 2>&1 &
                        
                        echo "Aplikasi berhasil dijalankan di background port ${APP_PORT}"
                    '''
                }
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
