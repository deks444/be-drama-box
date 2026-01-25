pipeline {
    agent {
        docker {
            image 'php:8.2-cli' // Menggunakan image PHP resmi
            args '-p 9004:9004' // Membuka port agar bisa diakses
        }
    }

    triggers {
        githubPush()
    }

    environment {
        APP_PORT = '9004'
    }

    stages {
        stage('Checkout') {
            steps {
                cleanWs()
                checkout scm
            }
        }

        stages {
        stage('Setup & Install') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'ENV_PATH')]) {
                    sh '''
                        # Install dependensi sistem yang diperlukan Laravel
                        apt-get update && apt-get install -y unzip libpq-dev libcurl4-gnutls-dev
                        
                        # Install Composer secara otomatis
                        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                        
                        cp "$ENV_PATH" .env
                        composer install --no-interaction --optimize-autoloader
                    '''
                }
            }
        }

stage('Run App') {
            steps {
                // Catatan: Di dalam Docker agent, proses akan mati jika stage selesai.
                // Untuk 'serve' di background, biasanya kita menggunakan Docker Compose 
                // atau membiarkannya tetap running di server host.
                sh 'php artisan serve --host=0.0.0.0 --port=${APP_PORT} &'
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
