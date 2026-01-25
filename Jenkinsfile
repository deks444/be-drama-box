pipeline {
    agent any

    options {
        // Menjaga agar log tidak terlalu penuh dan membatasi jumlah build lama
        buildDiscarder(logRotator(numToKeepStr: '10'))
        // Menghindari eksekusi simultan jika ada push bertubi-tubi
        disableConcurrentBuilds()
    }

    stages {
        stage('Cleanup') {
            steps {
                echo 'Cleaning up previous workspace...'
                deleteDir()
            }
        }

        stage('Checkout SCM') {
            steps {
                // Mengambil kode dari GitHub secara otomatis sesuai konfigurasi Job
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                echo 'Configuring Environment...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Menggunakan tanda kutip untuk menangani spasi pada path
                        // Menggunakan cat untuk memastikan file .env tercipta dengan benar
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'
                        sh 'chmod 600 .env'
                    }
                }
            }
        }

        stage('Install PHP Dependencies') {
            steps {
                echo 'Installing Composer Dependencies...'
                // --no-dev: tidak menginstall tool testing di produksi
                // --optimize-autoloader: mempercepat class loading Laravel
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev'
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Running Laravel Preparation Tasks...'
                script {
                    // Generate key jika belum ada (opsional jika sudah ada di secret .env)
                    sh 'php artisan key:generate --force'
                    
                    // Membuat link folder storage ke public
                    sh 'php artisan storage:link'
                    
                    // Set permission agar web server bisa menulis log & cache
                    sh 'chmod -R 775 storage bootstrap/cache'
                }
            }
        }

        stage('Database Migration') {
            steps {
                echo 'Running Database Migrations...'
                // --force wajib digunakan untuk menjalankan migrasi di environment production
                sh 'php artisan migrate --force'
            }
        }

        stage('Optimization') {
            steps {
                echo 'Caching Laravel Configuration and Routes...'
                sh 'php artisan config:cache'
                sh 'php artisan route:cache'
                sh 'php artisan view:cache'
            }
        }

        stage('Deployment') {
            steps {
                echo 'Finalizing Deployment...'
                // Jika menggunakan PM2, Docker, atau hanya restart PHP-FPM, taruh di sini
                // Contoh restart PHP-FPM:
                // sh 'sudo systemctl restart php8.2-fpm'
                echo 'Drama Box Auth is now live!'
            }
        }
    }

    post {
        success {
            echo 'Build and Deployment Successful!'
        }
        failure {
            echo 'Pipeline failed! Check console output for details.'
        }
        always {
            // Opsional: bersihkan cache build jika diperlukan
            echo 'Pipeline finished execution.'
        }
    }
}
