pipeline {
    agent any

    stages {
        stage('Preparation') {
            steps {
                // 1. Bersihkan workspace dari residu build sebelumnya
                cleanWs()
                
                // 2. Ambil kode dari repository
                checkout scm
            }
        }

        stage('Inject Environment') {
            steps {
                // 3. Menggunakan withCredentials untuk menangani file rahasia
                // Pastikan 'dramabox-auth-env' adalah ID yang tepat di Jenkins Credentials
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Gunakan single quotes (') untuk menghindari warning security
                        // dan memaksa shell Linux yang mengeksekusi penyalinan
                        sh 'cp -f ${SECRET_ENV} .env'
                        
                        // Verifikasi bahwa .env sekarang adalah FILE, bukan direktori
                        sh '[ -f .env ] && echo ".env file created successfully" || (echo ".env is not a file" && exit 1)'
                    }
                }
            }
        }

        stage('Install & Build') {
            steps {
                // 4. Jalankan perintah Laravel standar
                sh 'composer install --no-interaction --prefer-dist'
                sh 'php artisan key:generate --force'
                sh 'npm install && npm run build'
            }
        }

        stage('Run Tests') {
            steps {
                // 5. Verifikasi fitur Auth Drama Box
                sh 'php artisan test'
            }
        }
    }

    post {
        always {
            // Menghapus file .env setelah build selesai agar tidak tertinggal di server Jenkins
            sh 'rm -f .env'
        }
        success {
            echo "✅ Build Drama-Box-Auth Sukses!"
        }
        failure {
            echo "❌ Build Gagal. Periksa log di atas."
        }
    }
}
